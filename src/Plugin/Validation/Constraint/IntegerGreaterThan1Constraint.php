<?php

namespace Drupal\yammer\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a greater than 1 and integer.
 *
 * @Constraint(
 *   id = "YammerIntegerGreaterThan1",
 *   label = @Translation("Greater than 1 Integer", context = "Validation"),
 * )
 */
class IntegerGreaterThan1Constraint extends Constraint {

  /**
   * The message that will be shown if the value is not an integer.
   *
   * @var string
   */
  public $notInteger = 'ID is not an integer';

  /**
   * The message that will be shown if the value is not greater than 1.
   *
   * @var string
   */
  public $notGreaterThan1 = 'ID is not greater than 1';

}
