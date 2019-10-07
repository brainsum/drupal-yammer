<?php

namespace Drupal\yammer\Plugin\Field\FieldType;

use DateInterval;
use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Exception;
use function random_int;

/**
 * Provides a field type for yammer data.
 *
 * @FieldType(
 *   id = "yammer_user_data",
 *   label = @Translation("Yammer user data"),
 *   description = @Translation("Field for Yammer user data."),
 *   default_formatter = "yammer_user_data_formatter",
 *   default_widget = "yammer_user_data_widget",
 *   module = "yammer",
 *   category = @Translation("Yammer"),
 *   no_ui = TRUE,
 *   constraints = {
 *     "ComplexData" = {
 *       "expires_at" = {
 *         "Range" = {
 *           "min" = "-2147483648",
 *           "max" = "2147483648",
 *         }
 *       }
 *     }
 *   }
 * )
 *
 * @package Drupal\yammer\Plugin\Field\FieldType
 *
 * @todo: Add preSave().
 * - Check if token is encrypted.
 * -- If it is, skip.
 * -- If it is not, encrypt.
 */
class YammerUserData extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'token';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['token'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The encrypted token value'))
      ->setRequired(TRUE);

    $properties['expires_at'] = DataDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Expiry timestamp'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'token' => [
          'description' => 'The token.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => TRUE,
          'serialize' => FALSE,
        ],
        'expires_at' => [
          'description' => 'The token expiry date as a timestamp.',
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
      'unique keys' => [],
      'indexes' => [
        'expires_at' => ['expires_at'],
      ],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $token = $this->get('token')->getValue();
    return $token === NULL || $token === '';
  }

  /**
   * Returns the expiry date time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   A date object or NULL if there is no date.
   *
   * @todo: Double check.
   */
  public function getExpiryDateTime(): ?DrupalDateTime {
    if (!$this->expires_at) {
      return NULL;
    }

    return DrupalDateTime::createFromTimestamp($this->expires_at);
  }

  /**
   * Sets the date time object.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $dateTime
   *   An instance of a date time object.
   * @param bool $notify
   *   (optional) Whether to notify the entity of the change. Defaults to
   *   TRUE. If the update stems from the entity, set it to FALSE to avoid
   *   being notified again.
   *
   * @todo: Double check.
   */
  public function setExpiryDateTime(DrupalDateTime $dateTime, $notify = TRUE) {
    $this->expires_at = $dateTime->getTimestamp();
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = [];

    // Just pick a date in the past year. No guidance is provided by this Field
    // type.
    try {
      $randomDays = random_int(0, 365);
    }
    catch (Exception $exception) {
      $randomDays = 42;
    }

    $date = DrupalDateTime::createFromTimestamp(Drupal::time()->getRequestTime())
      ->sub(DateInterval::createFromDateString("{$randomDays} days"));
    $timestamp = $date->getTimestamp();

    $values['expires_at'] = $timestamp;

    return $values;
  }

}
