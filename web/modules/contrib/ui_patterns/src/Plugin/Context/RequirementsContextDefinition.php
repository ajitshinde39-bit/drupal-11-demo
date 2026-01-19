<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Context;

use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Defines a class to provide requirements context definitions.
 */
class RequirementsContextDefinition extends ContextDefinition {

  /**
   * Creates a new definition from an array requirements.
   *
   * @param array<string> $requirements
   *   The requirements array.
   * @param string|null $label
   *   The label of the context.
   *
   * @return RequirementsContextDefinition
   *   The requirements context definition instance.
   */
  public static function fromRequirements(array $requirements = [], ?string $label = NULL) : RequirementsContextDefinition {
    return new static("any", $label, TRUE, FALSE, NULL, NULL, ["RequiredArrayValues" => $requirements]);
  }

}
