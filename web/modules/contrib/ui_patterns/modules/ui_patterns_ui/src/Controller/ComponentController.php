<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Component;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Url;
use Drupal\ui_patterns_ui\Entity\ComponentFormDisplay;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a listing of component displays.
 */
final class ComponentController extends ControllerBase {

  /**
   * Redirects to the default route of a specified component.
   */
  public function forward(string $component_id):RedirectResponse {
    $component_plugin_manager = self::getComponentPluginManager();
    $component = $component_plugin_manager->find($component_id);
    return (new TrustedRedirectResponse($this->getDefaultRoute($component)
      ->toString()))
      ->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
  }

  /**
   * Renders a list of components in a table format.
   */
  public function render(): array {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('Component List'),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => '']),

    ];
    foreach ($this->load() as $component) {
      if ($row = $this->buildComponentRow($component)) {
        $build['table']['#rows'][$component->getPluginId()] = $row;
      }
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * The component plugin manager.
   */
  public static function getComponentPluginManager(): ComponentPluginManager {
    return \Drupal::service('plugin.manager.sdc');
  }

  /**
   * Load all components, sorted by provider and label.
   */
  public function load(): array {
    $component_plugin_manager = self::getComponentPluginManager();
    /* @phpstan-ignore method.notFound */
    $definitions = $component_plugin_manager->getNegotiatedSortedDefinitions();
    $plugin_ids = array_keys($definitions);
    // @phpstan-ignore-next-line
    return array_values(array_filter(array_map(
      // @phpstan-ignore-next-line
      [$component_plugin_manager, 'createInstance'],
      $plugin_ids
    )));
  }

  /**
   * Builds the header row for the component listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['provider'] = $this->t('Provider');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * Build one component row.
   */
  public function buildComponentRow(Component $component): array {
    $definition = $component->getPluginDefinition();
    $row['label'] = $component->metadata->name;
    $row['provider'] = is_array($definition) ? $definition['provider'] : '';
    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => $this->getComponentOperations($component),
    ];
    return $row;
  }

  /**
   * Retrieves the default route for a given component.
   *
   * This function determines the appropriate URL for the default form display
   * of a component. If a default display exists, it returns its URL; otherwise,
   * it constructs a route URL for adding a new form display.
   *
   * @param \Drupal\Core\Plugin\Component $component
   *   The component for which the default route is being retrieved.
   *
   * @return \Drupal\Core\Url
   *   The URL object representing the default route for the component.
   */
  private function getDefaultRoute(Component $component) {

    $default_display = ComponentFormDisplay::loadDefault($component->getPluginId());

    if ($default_display !== NULL) {
      return $default_display->toUrl();
    }
    else {
      $route = 'entity.component_form_display.' . $component->getPluginId() . '.add_form';
      return Url::fromRoute($route, [
        'component_id' => $component->getPluginId(),
      ]);
    }
  }

  /**
   * Returns component operations.
   */
  protected function getComponentOperations(Component $component): array {
    $operations['configure'] = [
      'title' => $this->t('Manage form display'),
      'url' => $this->getDefaultRoute($component),
      'attributes' => [],
      'weight' => 50,
    ];
    return $operations;
  }

}
