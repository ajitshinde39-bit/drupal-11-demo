<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;
use Drupal\ui_patterns\SchemaManager\Canonicalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'unknown' PropType.
 */
#[PropType(
  id: 'unknown',
  label: new TranslatableMarkup('Unknown'),
  description: new TranslatableMarkup('Prop type not found by the compatibility checker.'),
  default_source: NULL,
  schema: [],
  priority: 10,
)]
class UnknownPropType extends PropTypePluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected Canonicalizer $canonicalizer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ui_patterns.schema_canonicalizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $definition): array {
    $canon = $this->canonicalizer->canonicalize($definition);
    return [
      "⚠️ " . json_encode($canon),
    ];
  }

}
