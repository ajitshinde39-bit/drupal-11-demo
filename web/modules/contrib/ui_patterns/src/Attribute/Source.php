<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Source attribute.
 *
 * Plugin Namespace: Plugin\UiPatterns\Source.
 *
 * @ingroup ui_patterns
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Source extends Plugin {

  /**
   * Constructs a source attribute object.
   *
   * @param string $id
   *   A unique identifier for the source plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   Translatable label for the source plugin.
   * @param ?\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   = NULL
   *   Translatable description for the source plugin.
   * @param ?array $prop_types
   *   Prop types for this source.
   * @param class-string|null $deriver
   *   Deriver for this plugin.
   * @param ?array $tags
   *   Array of tags.
   * @param ?array $context_requirements
   *   Array of tags.
   * @param ?array $metadata
   *   Array of metadata.
   * @param ?array $context_definitions
   *   Array of context_definitions.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?array $prop_types = NULL,
    public readonly ?string $deriver = NULL,
    public readonly ?array $tags = [],
    public readonly ?array $context_requirements = [],
    public readonly ?array $metadata = [],
    public readonly ?array $context_definitions = [],
  ) {}

}
