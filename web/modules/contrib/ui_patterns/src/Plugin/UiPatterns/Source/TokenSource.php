<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source.
 *
 * Slot is explicitly added to prop_types to allow getPropValue
 * to return a renderable array in case of slot prop type.
 */
#[Source(
  id: 'token',
  label: new TranslatableMarkup('Token'),
  description: new TranslatableMarkup('Text with placeholder variables, replaced before display.'),
  prop_types: ['slot', 'string', 'url'],
  tags: [],
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity'), required: FALSE),
  ]
)]
class TokenSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      'value' => "",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = $this->getSetting('value') ?? "";
    $isSlot = ($this->propDefinition["ui_patterns"]["type_definition"]->getPluginId() === "slot");
    $tokenData = $this->getTokenData();

    // If the entity is new, we are probably in a preview system and there can
    // be side effects. So skip rendering.
    foreach ($tokenData as $tokenEntity) {
      if ($tokenEntity instanceof EntityInterface && $tokenEntity->id() === NULL) {
        return $isSlot ? [] : "";
      }
    }

    $bubbleable_metadata = new BubbleableMetadata();
    $value = $this->replaceTokens($value, $isSlot, $bubbleable_metadata);
    if (empty($value)) {
      return $isSlot ? [] : "";
    }
    if ($isSlot) {
      $build = [
        "#markup" => Markup::create($value),
      ];
      $bubbleable_metadata->applyTo($build);
      return $build;
    }
    return Html::escape($value);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['value'] = [
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('value'),
      // Tokens always start with a [ and end with a ].
      // '#pattern' => '^\[.+\]$',.
    ];
    $this->addRequired($form['value']);
    $this->addTokenTreeLink($form, "help");
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    if (empty($this->getSetting('value'))) {
      return [];
    }
    return [
      $this->getSetting('value'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() : array {
    $dependencies = parent::calculateDependencies();
    if ($this->moduleHandler->moduleExists('token')) {
      static::mergeConfigDependencies($dependencies, ["module" => ["token"]]);
    }
    return $dependencies;
  }

}
