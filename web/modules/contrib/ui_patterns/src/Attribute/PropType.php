<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a prop type attribute.
 *
 * Plugin Namespace: Plugin\UiPatterns\PropType.
 *
 * @ingroup ui_patterns
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PropType extends Plugin {

  /**
   * Constructs a prop type attribute object.
   *
   * @param string $id
   *   A unique identifier for the source plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   Translatable label for the source plugin.
   * @param ?\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The json schema of the plugin matches.
   * @param ?string $default_source
   *   (optional) The default source plugin.
   * @param ?array $convert_from
   *   (optional) A list of prop types it is possible to convert value from.
   * @param array $schema
   *   The json schema of the plugin matches.
   * @param ?int $priority
   *   (optional) The priority of the PropType.
   * @param ?array $typed_data
   *   (optional) The converted from data.
   * @param class-string|null $deriver
   *   (optional) Deriver for this plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $default_source = NULL,
    public readonly ?array $convert_from = [],
    public readonly array $schema = [],
    public readonly ?int $priority = NULL,
    public readonly ?array $typed_data = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
