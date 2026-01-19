<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Component form builder trait.
 */
trait FieldFormatterFormTrait {

  /**
   * Filter the field formatter plugin given the field definition.
   *
   * @param \Drupal\Core\Field\FormatterInterface[] $formatter_instances
   *   Array of field formatter plugin.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   *
   * @return array<string, FormatterInterface>
   *   Array for formatters, keyed by plugin id
   */
  private function filterFormatter(array $formatter_instances, FieldDefinitionInterface $field_definition): array {
    $filtered = [];
    foreach ($formatter_instances as $formatter) {
      if (!$formatter instanceof FormatterInterface || !$formatter::isApplicable($field_definition)) {
        continue;
      }
      $filtered[$formatter->getPluginId()] = $formatter;
    }
    return $filtered;
  }

  /**
   * Ajax callback for fields with AJAX callback to update form substructure.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The replaced form substructure.
   */
  public static function onFormatterTypeChange(array $form, FormStateInterface $form_state): array {
    $triggeringElement = $form_state->getTriggeringElement();
    // Dynamically return the dependent ajax for elements based on the
    // triggering element. This shouldn't be done statically because
    // settings forms may be different, e.g. for layout builder, core, ...
    if (!empty($triggeringElement['#array_parents'])) {
      $subformKeys = $triggeringElement['#array_parents'];
      // Remove the triggering element itself and add the 'settings' below key.
      array_pop($subformKeys);
      // Return the subform:
      $subform_settings_wrapper = NestedArray::getValue($form, array_merge($subformKeys, ['settings_wrapper']));
      $subform_settings = NestedArray::getValue($form, array_merge($subformKeys, ['settings']));
      $subform_third_party_settings = NestedArray::getValue($form, array_merge($subformKeys, ['third_party_settings']));
      return [
        '#prefix' => $subform_settings_wrapper['#prefix'],
        'settings' => $subform_settings,
        'third_party_settings' => $subform_third_party_settings,
        '#suffix' => $subform_settings_wrapper['#suffix'],
      ];
    }
    return [];
  }

}
