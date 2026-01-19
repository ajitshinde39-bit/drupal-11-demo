<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ComponentPluginManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Component form routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

  /**
   * Constructs a RouteSubscriber object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ComponentPluginManager $componentPluginManager,
  ) {

  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $components = $this->componentPluginManager->getAllComponents();
    $defaults = [];
    $path = '/admin/structure/component/';
    foreach ($components as $component) {
      $route = new Route(
        $path . $component->getPluginId() . '/form-display',
        [
          '_controller' => '\Drupal\ui_patterns_ui\Controller\ComponentController::forward',
          'component_id' => $component->getPluginId(),
        ] + $defaults,
        [
          '_permission' => 'administer component form display',
        ]
      );
      $collection->add("entity.component_form_display.{$component->getPluginId()}", $route);

      $route = new Route(
        $path . $component->getPluginId() . '/form-display/{form_mode_name}',
        [
          '_entity_form' => 'component_form_display.edit',
          '_title' => $component->metadata->name,
          'component_id' => $component->getPluginId(),
        ] + $defaults,
        [
          '_permission' => 'administer component form display',
        ]
      );
      $collection->add("entity.component_form_display.{$component->getPluginId()}.edit_form", $route);

      $route = new Route(
        $path . $component->getPluginId() . '/form-display/add',
        [
          '_entity_form' => 'component_form_display.add',
          '_title' => 'Add form display',
          'component_id' => $component->getPluginId(),
        ] + $defaults,
        [
          '_permission' => 'administer component',
        ]

      );
      $collection->add("entity.component_form_display.{$component->getPluginId()}.add_form", $route);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}
