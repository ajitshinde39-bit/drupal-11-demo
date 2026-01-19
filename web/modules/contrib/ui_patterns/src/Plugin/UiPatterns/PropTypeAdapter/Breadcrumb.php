<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropTypeAdapter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropTypeAdapter;
use Drupal\ui_patterns\PropTypeAdapterPluginBase;

/**
 * Plugin implementation of the prop_type_adapter.
 */
#[PropTypeAdapter(
  id: 'breadcrumb',
  label: new TranslatableMarkup('Breadcrumb'),
  description: new TranslatableMarkup('The data structure from breadcrumb.html.twig'),
  schema: [
    'type' => 'array',
    'items' => [
      'type' => 'object',
      'properties' => [
        'text' => ['type' => 'string'],
        'url' => ['$ref' => 'ui-patterns://url'],
        'attributes' => ['$ref' => 'ui-patterns://attributes'],
      ],
      'required' => ['text'],
    ],
  ],
  prop_type: 'links'
)]
final class Breadcrumb extends PropTypeAdapterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform(mixed $data): mixed {
    if (!is_array($data)) {
      return $data;
    }
    foreach ($data as $index => $item) {
      $item["text"] = $item["title"];
      unset($item["title"]);
      $data[$index] = $item;
    }
    return $data;
  }

}
