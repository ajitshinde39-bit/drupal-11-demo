<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library;

/**
 * Interface for story plugins.
 */
interface StoryInterface {

  /**
   * Only the story ID, unique in a single component scope.
   */
  public function machineName(): string;

  /**
   * Returns the translated plugin label.
   */
  public function name(): string;

  /**
   * Returns the translated plugin description.
   */
  public function description(): string;

  /**
   * Returns the component ID this story belongs to.
   *
   * Format: {provider}:{component_id}
   */
  public function component(): string;

  /**
   * Returns the story slots.
   */
  public function slots(): array;

  /**
   * Returns the story props.
   */
  public function props(): array;

}
