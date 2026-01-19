<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_test\Plugin\UiPatterns\Source;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source_provider.
 */
#[Source(
  id: 'context_foo',
  label: new TranslatableMarkup('Context Foo'),
  description: new TranslatableMarkup('Test Plugin with.'),
  prop_types: ['string'],
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity')),
  ]
)]
final class ContextFoo extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    return 'foo';
  }

}
