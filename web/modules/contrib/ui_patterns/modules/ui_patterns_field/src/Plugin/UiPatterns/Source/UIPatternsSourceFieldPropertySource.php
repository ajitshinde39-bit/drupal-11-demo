<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field\Plugin\UiPatterns\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\Plugin\UiPatterns\Source\FieldPropertySource;
use Drupal\ui_patterns_field\Plugin\Derivative\UIPatternsSourceFieldPropertySourceDeriver;
use Drupal\ui_patterns_field\Plugin\Field\FieldType\SourceValueItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the prop source.
 */
#[Source(
  id: 'ui_patterns_source',
  label: new TranslatableMarkup('Value from the component in field.'),
  description: new TranslatableMarkup('Map the prop/slot value to the one configured in the component stored the "Source" field.'),
  deriver: UIPatternsSourceFieldPropertySourceDeriver::class
)]
class UIPatternsSourceFieldPropertySource extends FieldPropertySource {

  /**
   * The component element builder.
   *
   * @var \Drupal\ui_patterns\Element\ComponentElementBuilder
   */
  protected $componentElementBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    // We keep the same constructor as SourcePluginBase.
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    // Defined in parent class FieldSourceBase.
    $instance->componentElementBuilder = $container->get('ui_patterns.component_element_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $items = $this->getEntityFieldItemList();
    $delta = (isset($this->context['ui_patterns:field:index'])) ? $this->getContextValue('ui_patterns:field:index') : 0;
    if (empty($items)) {
      return NULL;
    }
    /** @var \Drupal\Core\Field\FieldItemInterface $field_item_at_delta */
    $field_item_at_delta = $items->get($delta);
    if (!$field_item_at_delta || !($field_item_at_delta instanceof SourceValueItem)) {
      return NULL;
    }
    $source_id = $field_item_at_delta->source_id ?? 'component';
    if ($source_id !== 'component') {
      return NULL;
    }
    $source_configuration = $field_item_at_delta->source ?? [];
    if (!is_array($source_configuration)) {
      $source_configuration = [];
    }
    return $this->extractComponentPropValue($source_configuration);
  }

  /**
   * Extract the prop value from the source configuration.
   *
   * @param array $source_configuration
   *   The source configuration.
   *
   * @return mixed
   *   The prop value.
   */
  protected function extractComponentPropValue(array $source_configuration) : mixed {
    $component_configuration = $source_configuration['component'] ?? [];
    // $component_id = $component_configuration['component_id'] ?? NULL;
    $propDefinition = $this->getPropDefinition();
    $propId = $this->getPropId();
    /** @var \Drupal\ui_patterns\PropTypeInterface $propType */
    $propType = $propDefinition["ui_patterns"]["type_definition"];
    $contexts = $this->getContexts();
    $build = [];
    if ($propType->getPluginId() === "slot") {
      $sources = $component_configuration['slots'][$propId]["sources"] ?? [];
      foreach ($sources as $source) {
        $build = $this->componentElementBuilder->buildSource($build, $propId, $propDefinition, $source, $contexts);
      }
      return $build['#slots'][$propId] ?? [];
    }
    $build = [];
    $prop_source_config = (($propId === "variant") && !empty($component_configuration["variant_id"])) ? $component_configuration["variant_id"] : ($component_configuration['props'][$propId] ?? []);
    if (empty($prop_source_config)) {
      return NULL;
    }
    $build = $this->componentElementBuilder->buildSource($build, $propId, $propDefinition, $prop_source_config, $contexts);
    $property_value = $build['#props'][$propId] ?? NULL;
    if (empty($property_value)) {
      return NULL;
    }
    $prop_typ_types = [];
    if (isset($this->propDefinition['type'])) {
      // Type can be an array of types or a single type.
      $prop_typ_types = is_array($this->propDefinition['type']) ? $this->propDefinition['type'] : [$this->propDefinition['type']];
    }
    return $this->transTypeProp($property_value, $prop_typ_types);
  }

}
