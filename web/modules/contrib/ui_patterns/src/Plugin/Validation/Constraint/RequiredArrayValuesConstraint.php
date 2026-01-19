<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that all required values are present in an array.
 */
#[Constraint(
  id: 'RequiredArrayValues',
  label: new TranslatableMarkup('Required array values', [], ['context' => 'Validation']),
  type: ['list']
)]
class RequiredArrayValuesConstraint extends SymfonyConstraint {

  /**
   * The error message if a value is required.
   *
   * @var string
   */
  public string $requiredValueMessage = "'@value' is required.";

  /**
   * The error message if a value is not an array.
   *
   * @var string
   */
  public string $notArrayMessage = "Is not an array.";

  /**
   * Values which are allowed in the validated array.
   *
   * @var array<string>|null
   */
  public ?array $requiredValues;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): string {
    return 'requiredValues';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['requiredValues'];
  }

}
