<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * Interface for source plugins that have choices.
 */
interface SourceWithChoicesInterface {

  /**
   * Gets the choices for that source, given the context.
   *
   * @return array
   *   keys are choice ids, values are metadata, including labels.
   */
  public function getChoices(): array;

  /**
   * Get the source settings for a choice.
   *
   * @param string $choice_id
   *   The choice ID.
   *
   * @return array
   *   The minimal source settings.
   */
  public function getChoiceSettings(string $choice_id): array;

  /**
   * Get the choice from settings.
   *
   * @param array $settings
   *   The settings to get the choice from.
   *
   * @return string
   *   The choice id.
   */
  public function getChoice(array $settings): string;

}
