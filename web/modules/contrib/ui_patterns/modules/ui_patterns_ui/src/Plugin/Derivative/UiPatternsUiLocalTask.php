<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all component forms.
 */
class UiPatternsUiLocalTask extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  public function __construct(protected RouteProviderInterface $routeProvider, protected ComponentPluginManager $componentPluginManager, protected EntityTypeManagerInterface $entityTypeManager, TranslationInterface $stringTranslation) {
    $this->setStringTranslation($stringTranslation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('plugin.manager.sdc'),
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay[] $displays */
    $displays = $this->entityTypeManager->getStorage('component_form_display')->loadMultiple();
    $display_weight = 0;
    $components = $this->componentPluginManager->getAllComponents();
    foreach ($components as $component) {
      $this->derivatives["component_form_display.{$component->getPluginId()}.base"] = [
        'route_name' => "entity.component_form_display.{$component->getPluginId()}",
        'base_route' => "entity.component_form_display.{$component->getPluginId()}",
        'weight' => 10,
        'title' => $this->t('Overview'),
      ];
    }
    foreach ($displays as $display) {
      $this->derivatives["component_form_display.{$display->id()}"] = [
        'route_name' => "entity.component_form_display.{$display->getComponentId()}.edit_form",
        'base_route' => "entity.component_form_display.{$display->getComponentId()}.edit_form",
        'title' => $display->label(),
        'weight' => $display_weight,
        'cache_tags' => $display->getEntityType()->getListCacheTags(),
        'parent_id' => "ui_patterns_ui.form_displays:component_form_display.{$display->getComponentId()}.base",
        'route_parameters' => [
          'component_id' => $display->getComponentId(),
          'form_mode_name' => $display->getFormModeName(),
        ],
      ];
      $display_weight += 10;
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
