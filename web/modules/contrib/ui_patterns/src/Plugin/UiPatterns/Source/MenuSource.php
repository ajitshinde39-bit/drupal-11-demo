<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\Entity\Menu;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\LinksPropType;
use Drupal\ui_patterns\SourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'menu',
  label: new TranslatableMarkup('Menu'),
  description: new TranslatableMarkup('Provides a generic Menu source..'),
  prop_types: ['links']
)]
class MenuSource extends SourcePluginBase {

  /**
   * The menu ID.
   *
   * @var string
   */
  protected $menuId;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $plugin = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $plugin->menuLinkTree = $container->get('menu.link_tree');
    $plugin->entityTypeManager = $container->get('entity_type.manager');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      'menu' => NULL,
      'level' => 1,
      'depth' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $menu_id = $this->getSetting('menu');
    if (!$menu_id) {
      return [];
    }
    $this->menuId = $menu_id;
    return $this->getMenuItems();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form["menu"] = [
      '#type' => 'select',
      '#title' => $this->t("Menu"),
      '#options' => $this->getMenuList(),
      '#default_value' => $this->getSetting('menu'),
    ];
    $options = range(0, $this->menuLinkTree->maxDepth());
    unset($options[0]);
    $form['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => $this->getSetting('level'),
      '#options' => $options,
    ];
    $options[0] = $this->t('Unlimited');
    $form['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => $this->getSetting('depth'),
      '#options' => $options,
      '#description' => $this->t(
        'This maximum number includes the initial level and the final display is dependant of the component template.'
      ),
    ];
    return $form;
  }

  /**
   * Get menus list.
   *
   * @return array
   *   List of menus.
   */
  private function getMenuList(): array {
    $all_menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
    $menus = [
      "" => "(None)",
    ];
    foreach ($all_menus as $id => $menu) {
      $menus[$id] = $menu->label();
    }
    asort($menus);
    return $menus;
  }

  /**
   * Get menu items.
   *
   * @return array
   *   List of items.
   */
  private function getMenuItems(): array {
    $menuLinkTree = $this->menuLinkTree;
    $level = (int) $this->getSetting('level');
    $depth = (int) $this->getSetting('depth');
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth($level);

    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(
        min($level + $depth - 1, $menuLinkTree->maxDepth())
      );
    }

    $tree = $menuLinkTree->load($this->menuId, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $menuLinkTree->transform($tree, $manipulators);
    $tree = $menuLinkTree->build($tree);
    if (\array_key_exists("#items", $tree)) {
      $variables = [
        "items" => $tree["#items"],
      ];
      $this->moduleHandler->invokeAll("preprocess_menu", [&$variables]);
      $variables["items"] = LinksPropType::normalize($variables["items"], $this->getPropDefinition());
      return $variables["items"];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterComponent(array $element): array {
    if (!$this->menuId) {
      return $element;
    }

    $cache = CacheableMetadata::createFromRenderArray($element);
    $cache->addCacheTags(['config:system.menu.' . $this->menuId]);
    $cache->applyTo($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() : array {
    $dependencies = parent::calculateDependencies();
    $menu_id = $this->getSetting('menu');
    if (!$menu_id) {
      return $dependencies;
    }
    $menu = Menu::load($menu_id);
    if (!$menu) {
      return $dependencies;
    }
    SourcePluginBase::mergeConfigDependencies($dependencies, [$menu->getConfigDependencyKey() => [$menu->getConfigDependencyName()]]);
    return $dependencies;
  }

}
