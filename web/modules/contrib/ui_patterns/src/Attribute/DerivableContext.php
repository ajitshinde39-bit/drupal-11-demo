<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a DerivableContext attribute.
 *
 * Plugin Namespace: Plugin\UiPatterns\DerivableContext.
 *
 * @ingroup ui_patterns
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DerivableContext extends Plugin {

  /**
   * Constructs a derivable context object.
   *
   * @param string $id
   *   A unique identifier for the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   Translatable label for the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) Translatable description for the plugin.
   * @param ?array $metadata
   *   (optional) Array of metadata.
   * @param class-string|null $deriver
   *   (optional) Deriver for this plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?array $metadata = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
