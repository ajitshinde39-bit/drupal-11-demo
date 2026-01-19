<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formatter to render the file URI to its download path.
 */
#[FieldFormatter(
  id: 'ui_patterns_source',
  label: new TranslatableMarkup('Render source (UI Patterns)'),
  field_types: ['ui_patterns_source'],
)]
class SourceFormatter extends FormatterBase {

  /**
   * The component element builder.
   *
   * @var \Drupal\ui_patterns\Element\ComponentElementBuilder
   */
  protected $componentElementBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->componentElementBuilder = $container->get('ui_patterns.component_element_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $fake_build = [];
    $contexts = $this->getComponentSourceContexts($items);
    $contexts['ui_patterns:lang_code'] = new Context(new ContextDefinition('any'), $langcode);
    $contexts['ui_patterns:field:items'] = new Context(new ContextDefinition('any'), $items);
    for ($field_item_index = 0; $field_item_index < $items->count(); $field_item_index++) {
      $contexts['ui_patterns:field:index'] = new Context(new ContextDefinition('integer'), $field_item_index);
      $source_with_configuration = $items->get($field_item_index)->getValue();
      $fake_build = $this->componentElementBuilder->buildSource($fake_build, 'content', [], $source_with_configuration, $contexts);
    }
    $build = $fake_build['#slots']['content'] ?? [];
    $build['#cache'] = $fake_build['#cache'] ?? [];
    return $build;
  }

}
