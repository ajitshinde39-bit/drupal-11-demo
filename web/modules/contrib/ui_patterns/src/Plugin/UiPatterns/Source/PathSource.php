<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'path',
  label: new TranslatableMarkup('Path'),
  description: new TranslatableMarkup('Internal path.'),
  prop_types: ['url'],
  tags: ['widget', 'widget:dismissible']
)]
class PathSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = $this->getSetting('value');
    if (is_scalar($value)) {
      $value = $this->replaceTokens($value, FALSE);
    }
    elseif (isset($value["route_name"])) {
      $value["route_name"] = $this->replaceTokens($value["route_name"], FALSE);
    }
    return $this->getUrlFromRoute($value);
  }

  /**
   * Get URL from route.
   */
  private function getUrlFromRoute(mixed $value): string {
    if (is_scalar($value)) {
      return (string) $value;
    }
    if (!isset($value["route_name"])) {
      return "";
    }
    $url = Url::fromRoute($value["route_name"], $value["route_parameters"] ?? []);
    return $url->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $value = $this->getSetting('value');
    $value = $this->getUrlFromRoute($value);
    $form['value'] = [
      '#type' => 'path',
      '#default_value' => $value,
      '#description' => $this->t("Enter an internal path"),
    ];
    $this->addRequired($form['value']);
    return $form;
  }

}
