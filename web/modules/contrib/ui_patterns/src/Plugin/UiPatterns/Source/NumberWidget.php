<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginPropValueWidget;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'number',
  label: new TranslatableMarkup('Number'),
  description: new TranslatableMarkup('Numeric input, with special numeric validation.'),
  prop_types: ['number'],
  tags: ['widget', 'widget:dismissible']
)]
class NumberWidget extends SourcePluginPropValueWidget {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = parent::getPropValue();
    if (self::isConvertibleToString($value)) {
      $value = $this->replaceTokens($value, FALSE);
    }
    // Add 0 to automatically cast to a float OR an integer.
    if (empty($value)) {
      return $value;
    }
    return $value + 0;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['value'] = [
      '#type' => 'number',
      '#default_value' => $this->getSetting('value'),
      '#step' => 0.01,
    ];
    if ($this->propDefinition["type"] === "integer") {
      $form['value']['#step'] = 1;
    }
    // Because of SDC's ComponentMetadata::parseSchemaInfo() which is adding
    // "object" type to all props to "allows deferring rendering in Twig to the
    // render pipeline". Remove it as soon as this weird mechanism is removed
    // from SDC.
    $type = $this->propDefinition["type"];
    if (is_array($type) && in_array("integer", $type) && !in_array("number", $type, TRUE)) {
      $form['value']['#step'] = 1;
    }
    if (is_string($type) && $type === 'integer') {
      $form['value']['#step'] = 1;
    }
    if (isset($this->propDefinition["minimum"])) {
      $form['value']['#min'] = $this->propDefinition["minimum"];
    }
    if (isset($this->propDefinition["maximum"])) {
      $form['value']['#max'] = $this->propDefinition["maximum"];
    }
    $this->addRequired($form['value']);
    return $form;
  }

}
