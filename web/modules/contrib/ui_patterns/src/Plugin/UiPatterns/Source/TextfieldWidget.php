<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginPropValueWidget;
use Drupal\ui_patterns\UnicodePatternValidatorTrait;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'textfield',
  label: new TranslatableMarkup('Textfield'),
  description: new TranslatableMarkup('One-line text field.'),
  prop_types: ['string', 'identifier'],
  tags: ['widget']
)]
class TextfieldWidget extends SourcePluginPropValueWidget implements TrustedCallbackInterface {

  use UnicodePatternValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['validateUnicodePattern'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['value'] = [
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('value'),
    ];
    $this->addRequired($form['value']);
    $description = [];
    if (isset($this->propDefinition["pattern"])) {
      $form['value']['#pattern_unicode'] = $this->propDefinition["pattern"];
      $description[] = $this->t("Constraint: @pattern", ["@pattern" => $this->propDefinition["pattern"]]);
    }
    if (isset($this->propDefinition["maxLength"])) {
      $form['value']['#maxlength'] = $this->propDefinition["maxLength"];
      $form['value']['#size'] = $this->propDefinition["maxLength"];
      $description[] = $this->t("Max length: @length", ["@length" => $this->propDefinition["maxLength"]]);
    }
    if (!isset($this->propDefinition["pattern"]) && isset($this->propDefinition["minLength"])) {
      $form['value']['#pattern'] = "^.{" . $this->propDefinition["minLength"] . ",}$";
      $description[] = $this->t("Min length: @length", ["@length" => $this->propDefinition["minLength"]]);
    }
    if ((isset($form['value']['#pattern']) || isset($form['value']['#pattern_unicode'])) &&
        !isset($form['value']['#title'])) {
      $form['value']['#title'] = $this->propDefinition["title"] ?? $this->propId;
    }
    $form['value']["#description"] = implode("; ", $description);
    // @todo change when issue https://www.drupal.org/project/drupal/issues/2633550 is fixed.
    if (isset($form['value']['#pattern_unicode'])) {
      $form['value']['#element_validate'][] = [static::class, 'validateUnicodePattern'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = parent::getPropValue();
    if (empty($value)) {
      return $value;
    }
    return $this->replaceTokens($value, FALSE);
  }

}
