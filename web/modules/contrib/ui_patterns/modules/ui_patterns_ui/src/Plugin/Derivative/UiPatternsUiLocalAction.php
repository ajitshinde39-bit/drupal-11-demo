<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions for all component forms.
 */
class UiPatternsUiLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Constructs a UiPatternsUiLocalAction object.
   */
  public function __construct(protected RouteProviderInterface $routeProvider, protected ComponentPluginManager $componentPluginManager, protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('plugin.manager.sdc'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $components = $this->componentPluginManager->getAllComponents();
    foreach ($components as $component) {
      $this->derivatives["component_form_display.{$component->getPluginId()}"] = [
        'route_name' => "entity.component_form_display.{$component->getPluginId()}.add_form",
        'title' => $this->t('Add form display'),
        'appears_on' => ["entity.component_form_display.{$component->getPluginId()}.edit_form"],
      ];
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
