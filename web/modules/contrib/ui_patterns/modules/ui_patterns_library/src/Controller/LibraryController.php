<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ui_patterns\ComponentPluginManager;
use Drupal\ui_patterns_library\StoryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Component library's overview and single pages.
 *
 * @package Drupal\ui_patterns_library\Controller
 */
class LibraryController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected ComponentPluginManager $componentPluginManager,
    protected StoryPluginManager $storyPluginManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sdc'),
      $container->get('plugin.manager.component_story'),
    );
  }

  /**
   * Title callback.
   *
   * @param string $provider
   *   Module or theme providing the component.
   * @param string $machineName
   *   Component machine name.
   *
   * @return string
   *   Pattern label.
   */
  public function title(string $provider, string $machineName) {
    $id = $provider . ":" . $machineName;
    $definition = $this->componentPluginManager->getDefinition($id);
    return $definition["name"];
  }

  /**
   * Returns preview component definition.
   *
   * The definition is passed to the preview template.
   */
  private function getPreviewComponentDefinition(string $id): array {
    $definition = $this->componentPluginManager->negotiateDefinition($id);
    $definition['stories'] = $this->storyPluginManager->getComponentStories($id);
    return $definition;
  }

  /**
   * Render a single component page.
   *
   * @param string $provider
   *   Module or theme providing the component.
   * @param string $machineName
   *   Component machine name.
   *
   * @return array
   *   Return render array.
   */
  public function single(string $provider, string $machineName) {
    $id = $provider . ":" . $machineName;

    return [
      '#theme' => 'ui_patterns_single_page',
      '#component' => $this->getPreviewComponentDefinition($id),
    ];
  }

  /**
   * Provider title callback.
   *
   * @param string $provider
   *   Module or theme providing the component.
   *
   * @return string
   *   Provider label.
   */
  public function providerTitle(string $provider) {
    // @todo the label
    return $provider;
  }

  /**
   * Render the components overview page for a specific provider.
   *
   * @param string $provider
   *   Module or theme providing the component.
   *
   * @return array
   *   Patterns overview page render array.
   */
  public function provider(string $provider) {
    $groups = [];
    $grouped_definitions = $this->componentPluginManager->getGroupedDefinitions();
    // @todo move to componentPluginManager?
    foreach ($grouped_definitions as $group_id => $definitions) {
      foreach ($definitions as $definition_id => $definition) {
        if ($definition['provider'] == $provider) {
          $definition['stories'] = $this->storyPluginManager->getComponentStories($definition_id);
          $groups[$group_id][$definition_id] = $definition;
        }
      }
    }
    return [
      '#theme' => 'ui_patterns_overview_page',
      '#groups' => $groups,
    ];
  }

  /**
   * Render the components overview page.
   *
   * @return array
   *   Patterns overview page render array.
   */
  public function overview() {
    $groups = [];
    $grouped_definitions = $this->componentPluginManager->getNegotiatedGroupedDefinitions();
    foreach ($grouped_definitions as $group_id => $definitions) {
      foreach ($definitions as $definition_id => $definition) {
        $groups[$group_id][$definition_id] = $this->getPreviewComponentDefinition($definition['id']);
      }
    }
    return [
      '#theme' => 'ui_patterns_overview_page',
      '#groups' => $groups,
    ];
  }

}
