<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\MapFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Field Type to store UI Patterns source configuration.
 *
 * @property string $source_id
 * @property string $source
 */
#[FieldType(
  id: "ui_patterns_source",
  label: new TranslatableMarkup("Source (UI Patterns)"),
  description: new TranslatableMarkup("Store an UI Patterns source configuration"),
  default_widget: "ui_patterns_source",
  default_formatter: "ui_patterns_source",
  list_class: MapFieldItemList::class,
)]
class SourceValueItem extends MapItem {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'source_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'source_id' => [
          'type' => 'varchar_ascii',
          'length' => 255,

        ],
        'source' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

}
