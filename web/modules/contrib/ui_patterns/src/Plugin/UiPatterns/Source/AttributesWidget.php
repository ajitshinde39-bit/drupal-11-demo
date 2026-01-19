<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\AttributesTrait;
use Drupal\ui_patterns\SourcePluginPropValueWidget;
use Drupal\ui_patterns\UnicodePatternValidatorTrait;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'attributes',
  label: new TranslatableMarkup('Attributes'),
  description: new TranslatableMarkup('Textfield with double-quoted values or a space-separated list of HTML classes.'),
  prop_types: ['attributes'],
  tags: ['widget', 'widget:dismissible'],
)]
class AttributesWidget extends SourcePluginPropValueWidget implements TrustedCallbackInterface {

  use UnicodePatternValidatorTrait;
  use AttributesTrait;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['validateUnicodePattern'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    // In UI Patterns Settings, we built the Attribute object here. It is not
    // possible anymore because SDC will not validate it against the prop
    // type schema.
    return static::convertValueToAttributesMapping(parent::getPropValue());
  }

  /**
   * {@inheritdoc}
   */
  protected function convertPropValueToStoredValue(mixed $propValue): mixed {
    if ($propValue === NULL) {
      return NULL;
    }
    if ($propValue instanceof Attribute) {
      return (string) $propValue;
    }
    if (!is_array($propValue)) {
      return NULL;
    }
    $attributes = new Attribute($propValue);
    // Trim because Attribute::__toString() add a space on the left as it is
    // intended to be printed in HTML.
    return trim((string) $attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    // Attributes are associative arrays, but this source plugin is storing
    // them as string in config.
    // It would be better to use something else than a textfield one day.
    $value = $this->getSetting('value') ?? '';
    $form['value'] = [
      '#type' => 'textfield',
      '#default_value' => $value,
    ];
    if (static::isValueForAttributes($value)) {
      $form['value']['#pattern_unicode'] = static::buildAttributesRegexPattern();
      $form['value']['#pattern_error'] = $this->t('Format is not HTML attributes with double-quoted values.');
    }
    else {
      $form['value']['#pattern_unicode'] = static::buildClassRegexPattern();
      $form['value']['#pattern_error'] = $this->t('Not a valid space-separated list of HTML classes.');
    }
    // To allow form errors to be displayed correctly.
    $this->addRequired($form['value']);
    $form['value']['#placeholder'] = 'class="hidden" title="Lorem ipsum"';
    $form['value']['#description'] = $this->t("HTML attributes with double-quoted values or a space-separated list of HTML classes.");

    $form['value']['#element_validate'][] = [static::class, 'validateUnicodePattern'];
    return $form;
  }

}
