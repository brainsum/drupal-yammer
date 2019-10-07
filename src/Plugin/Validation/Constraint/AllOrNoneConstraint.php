<?php

namespace Drupal\yammer\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a greater than 0 and integer.
 *
 * @Constraint(
 *   id = "YammerAllOrNone",
 *   label = @Translation("All is set or none of them", context = "Validation"),
 * )
 */
class AllOrNoneConstraint extends Constraint {

  /**
   * Default message.
   *
   * Will be shown if from the values are not set all or none of them.
   *
   * @var string
   */
  public $notAllOrNone = 'The Yammer group Name and ID both need to be filled, or both need to be left empty.';

}
