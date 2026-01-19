<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Context;

use Drupal\Core\Plugin\Context\Context;

/**
 * Class to provide a specific entity context.
 */
class RequirementsContext extends Context {

  /**
   * Gets a context object from an entity.
   *
   * @param array<string> $values
   *   Requirements that provides a context.
   * @param string $label
   *   (optional) The label of the context.
   *
   * @return static
   */
  public static function fromValues(array $values, $label = NULL) {
    return new static(RequirementsContextDefinition::fromRequirements([], $label), $values);
  }

  /**
   * Check if a values is present in the context.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   True if the value is present in the context.
   */
  public function hasValue(string $value) : bool {
    return in_array($value, $this->getContextValue() ?? []);
  }

  /**
   * Add values to the context_requirements context.
   *
   * @param array<string> $values
   *   The values to add to context_requirements context.
   * @param array<\Drupal\Core\Plugin\Context\ContextInterface> $contexts
   *   The contexts.
   *
   * @return array<\Drupal\Core\Plugin\Context\ContextInterface>
   *   The contexts.
   */
  public static function addToContext(array $values, array $contexts): array {
    if (array_key_exists("context_requirements", $contexts) && $contexts["context_requirements"] instanceof RequirementsContext) {
      $contexts["context_requirements"] = static::fromValues(array_merge($contexts["context_requirements"]->getContextValue(), $values));
    }
    else {
      $contexts["context_requirements"] = static::fromValues($values);
    }

    return $contexts;
  }

  /**
   * Remove values from the context_requirements context.
   *
   * @param array<string> $values
   *   The values to add to context_requirements context.
   * @param array<\Drupal\Core\Plugin\Context\ContextInterface> $contexts
   *   The contexts.
   *
   * @return array<\Drupal\Core\Plugin\Context\ContextInterface>
   *   The contexts.
   */
  public static function removeFromContext(array $values, array $contexts): array {
    if (array_key_exists("context_requirements", $contexts) && ($contexts["context_requirements"] instanceof RequirementsContext)) {
      $contexts["context_requirements"] = static::fromValues(array_filter((array) $contexts["context_requirements"]->getContextValue(), function ($value) use ($values) {
        return !in_array($value, $values, TRUE);
      }));
    }
    return $contexts;
  }

}
