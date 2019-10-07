<?php

namespace Drupal\yammer\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the AllOrNoneConstraint constraint.
 */
class AllOrNoneConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    foreach ($items->getValue() as $value) {
      if (empty($value)) {
        $this->context->addViolation($constraint->notAllOrNone);
      }
    }
  }

}
