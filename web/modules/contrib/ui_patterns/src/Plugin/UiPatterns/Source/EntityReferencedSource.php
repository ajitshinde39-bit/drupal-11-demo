<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'entity_reference',
  label: new TranslatableMarkup('[Entity] ➜ Referenced [Entity]'),
  description: new TranslatableMarkup('Data from a referenced entities'),
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity'), required: TRUE),
  ],
  tags: [
    'context_switcher',
  ]
)]
class EntityReferencedSource extends DerivableContextSourceBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form["derivable_context"]["#title"] = $this->t("Referenced entities");
    return $form;
  }

  /**
   * Get the context to pass to the derivable context plugin.
   *
   * @return array
   *   The context.
   */
  protected function getContextForDerivation(): array {
    return array_filter($this->context, function ($key) {
      return $key !== 'ui_patterns:field:index';
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * {@inheritDoc}
   */
  protected function getDerivableContextsOptions() : array {
    $options = parent::getDerivableContextsOptions();
    $groups = self::getDerivableContextOptionGroups($options);
    $option_to_group = [];
    foreach ($groups as $group => $group_keys) {
      foreach ($group_keys as $group_key) {
        $option_to_group[$group_key] = $group;
      }
    }
    $returned = [];
    foreach ($options as $option_key => $option_value) {
      if (!isset($option_to_group[$option_key])) {
        $returned[$option_key] = (string) $this->getSimplifiedOptionWording($option_value);
      }
    }
    foreach ($groups as $group_key => $grouped_option_keys) {
      $group = (string) $options[$group_key];
      $returned[$group] = [$group_key => $this->t('- All -')];
      foreach ($grouped_option_keys as $grouped_option_key) {
        if ($grouped_option_key !== $group_key) {
          $label = (string) $options[$grouped_option_key];
          // @phpstan-ignore-next-line
          $returned[$group][$grouped_option_key] = explode(" referenced by ", $label)[0];
        }
      }
    }
    return $returned;
  }

  /**
   * Get DerivableContext option groups.
   *
   * @param array<string, mixed> $options
   *   The options.
   *
   * @return array<string, array<string> >
   *   The option groups.
   */
  private static function getDerivableContextOptionGroups(array $options) : array {
    $groups = [];
    foreach (array_keys($options) as $option_key) {
      $exploded_option = explode(":", $option_key);
      $exploded_option[count($exploded_option) - 1] = "";
      $group_option = implode(":", $exploded_option);
      if (!isset($options[$group_option])) {
        continue;
      }
      if (!isset($groups[$group_option])) {
        $groups[$group_option] = [];
      }
      $groups[$group_option][] = $option_key;
    }
    // Remove single groups.
    return array_filter($groups, function ($grouped_option_keys) {
      return count($grouped_option_keys) > 1;
    });
  }

  /**
   * {@inheritDoc}
   */
  protected function getSourcesTagFilter(): array {
    return [
      "widget:dismissible" => FALSE,
      "widget" => FALSE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  protected function getDerivationTagFilter(): ?array {
    return [
      // "entity" => TRUE,
      "entity_referenced" => TRUE,
    ];
  }

  /**
   * Return simplified wording for an option.
   *
   * @param mixed $option_value
   *   The option value.
   *
   * @return mixed
   *   The simplified option.
   */
  protected function getSimplifiedOptionWording(mixed $option_value): mixed {
    if (!($option_value instanceof TranslatableMarkup)) {
      return $option_value;
    }
    $arguments = $option_value->getArguments();

    // Modify @bundle to keep only the part before any parentheses.
    if (isset($arguments['@bundle'])) {
      $bundle_value = $arguments['@bundle'];

      // Keep only the part before parentheses, if present.
      if (is_string($bundle_value) &&
        preg_match('/^(.*?)\s*\(/', $bundle_value, $matches)) {
        $arguments['@bundle'] = trim($matches[1]);
      }
    }

    return new TranslatableMarkup(
      '@field (@bundle)',
      $arguments,
      $option_value->getOptions()
    );
  }

}
