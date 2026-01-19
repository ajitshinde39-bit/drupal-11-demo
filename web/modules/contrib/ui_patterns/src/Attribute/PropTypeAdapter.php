<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Source attribute.
 *
 * Plugin Namespace: Plugin\UiPatterns\PropTypeAdapter.
 *
 * @ingroup ui_patterns
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PropTypeAdapter extends Plugin {

  /**
   * Constructs a prop type adapter attribute object.
   *
   * @param string $id
   *   A unique identifier for the source plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   Translatable label for the source plugin.
   * @param ?\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   (optional) Translatable description for the source plugin.
   * @param array $schema
   *   The json schema of the plugin matches.
   * @param string $prop_type
   *   The prop type plugin.
   * @param class-string|null $deriver
   *   (optional) Deriver for this plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description,
    public readonly array $schema,
    public readonly string $prop_type,
    public readonly ?string $deriver = NULL,
  ) {}

}
