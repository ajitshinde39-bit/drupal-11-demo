<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropTypeAdapter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\ui_patterns\Attribute\PropTypeAdapter;
use Drupal\ui_patterns\PropTypeAdapterPluginBase;

/**
 * Plugin implementation of the prop_type_adapter.
 */
#[PropTypeAdapter(
  id: 'ns_attributes',
  label: new TranslatableMarkup('Attributes (PHP namespace)'),
  description: new TranslatableMarkup('SDC allows PHP namespaces as JSON schema types.'),
  schema: ['type' => ['Drupal\Core\Template\Attribute', '\Drupal\Core\Template\Attribute']],
  prop_type: 'attributes'
)]
final class NamespacedAttributes extends PropTypeAdapterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform(mixed $data): mixed {
    if (!is_array($data)) {
      return $data;
    }
    // Component using this type are expecting a PHP object.
    // It is OK to send an attribute object instead of a primitive because
    // SDC is not validating props with PHP namespaces as type.
    return new Attribute($data);
  }

}
