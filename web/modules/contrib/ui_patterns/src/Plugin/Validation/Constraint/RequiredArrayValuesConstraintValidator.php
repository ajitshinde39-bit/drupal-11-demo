<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the RequiredArrayValues constraint.
 */
class RequiredArrayValuesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof RequiredArrayValuesConstraint);
    if (!is_array($value)) {
      $this->context->buildViolation($constraint->notArrayMessage)->addViolation();
      return;
    }
    $values = array_values($value);
    foreach ($constraint->requiredValues as $requiredValue) {
      if (!in_array($requiredValue, $values)) {
        $this->context->buildViolation($constraint->requiredValueMessage)->setParameter('@value', $requiredValue)
          ->atPath((string) $requiredValue)->setInvalidValue($requiredValue)
          ->addViolation();
      }
    }
  }

}
