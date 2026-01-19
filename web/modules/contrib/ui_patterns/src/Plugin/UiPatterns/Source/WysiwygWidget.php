<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Entity\FilterFormat;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'wysiwyg',
  label: new TranslatableMarkup('Wysiwyg'),
  description: new TranslatableMarkup('Wysiwyg editor'),
  prop_types: ['slot'],
  tags: ['widget']
)]
class WysiwygWidget extends SourcePluginBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['textFormat'];
  }

  /**
   * Customize the text_format element.
   *
   * @param array $element
   *   Element to process.
   *
   * @return array
   *   Processed element
   */
  public static function textFormat(array $element) : array {
    if (!isset($element['#ui_patterns']) || !$element['#ui_patterns']) {
      return $element;
    }
    if (isset($element['format']['format']['#access']) &&
      !$element['format']['format']['#access']) {
      // See code at Drupal\filter\Element\TextFormat::processTextFormat()
      // when the format is not accessible, we need to make it accessible.
      $element['format']['format']['#access'] = TRUE;
    }
    $config = \Drupal::configFactory()->get('filter.settings');
    $fallback_format = $config->get('fallback_format');
    $formats = filter_formats();
    // We add the fallback format to the list of options if removed.
    if (array_key_exists($fallback_format, $formats)
      && !array_key_exists($fallback_format, $element['format']['format']['#options'])) {
      $element['format']['format']['#options'][$fallback_format] = $formats[$fallback_format]->label();
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      'value' => [
        "value" => '',
        "format" => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    return [
      "#type" => "processed_text",
      "#text" => $this->getSetting('value')['value'],
      "#format" => $this->getSetting('value')['format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $value = $this->getSetting('value');
    $element = [
      '#type' => 'text_format',
      '#ui_patterns' => TRUE,
    ];
    if (is_array($value) && array_key_exists("value", $value)) {
      $element['#default_value'] = $value['value'];
    }
    if (is_array($value) && array_key_exists("format", $value) && !empty($value['format'])) {
      $element['#format'] = $value['format'];
    }
    else {
      $element['#format'] = filter_fallback_format();
    }
    $form['value'] = $element;
    $this->addRequired($form['value']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $value = $this->getSetting('value')['value'] ?? '';
    return [
      substr(strip_tags($value), 0, 20),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() : array {
    $dependencies = parent::calculateDependencies();
    $value = $this->getSetting('value');
    if (!is_array($value) || !array_key_exists("format", $value)) {
      return $dependencies;
    }
    $format = FilterFormat::load($value["format"]);
    if ($format) {
      SourcePluginBase::mergeConfigDependencies($dependencies, [$format->getConfigDependencyKey() => [$format->getConfigDependencyName()]]);
    }
    return $dependencies;
  }

}
