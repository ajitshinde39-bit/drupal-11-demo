<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers layout builder and field layout resolver.
 */
class UiPatternsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $modules = $container->getParameterBag()->get('container.modules');
    if (is_array($modules) === FALSE) {
      return;
    }
    if (isset($modules['layout_builder'])) {
      $service = $container->register('ui_patterns.layout_builder_context_entity_resolver', '\Drupal\ui_patterns\Resolver\LayoutBuilderContextEntityResolver');
      $service->addArgument(new Reference('current_route_match'));
      $service->addArgument(new Reference('entity_type.manager'));
      $service->addArgument(new Reference('ui_patterns.sample_entity_generator'));
      $service->addTag('ui_patterns.context_entity_resolver', ['priority' => 10]);
    }
    if (isset($modules['field_layout'])) {
      $service = $container->register('ui_patterns.field_layout_context_entity_resolver', '\Drupal\ui_patterns\Resolver\FieldLayoutContextEntityResolver');
      $service->addArgument(new Reference('entity_type.manager'));
      $service->addArgument(new Reference('ui_patterns.sample_entity_generator'));
      $service->addTag('ui_patterns.context_entity_resolver', ['priority' => 20]);
    }

  }

}
