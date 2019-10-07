<?php

namespace Drupal\yammer\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of Yammer feed.
 *
 * @FieldType(
 *   id = "yammer_feed",
 *   label = @Translation("Yammer Feed"),
 *   description = @Translation("Field for Yammer Group ID and Name."),
 *   default_formatter = "yammer_feed_formatter",
 *   default_widget = "yammer_feed_widget",
 *   module = "yammer",
 *   category = @Translation("Yammer"),
 *   constraints = {
 *    "YammerAllOrNone" = {}
 *   },
 * )
 */
class YammerFeed extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['group_id'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Group ID'))
      ->setDescription(new TranslatableMarkup('Yammer group ID, value needs to be numeric unsigned.'))
      ->addConstraint('YammerIntegerGreaterThan1');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('group_id')->getValue();
    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'group_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
          'size' => 'big',
        ],
      ],
    ];
  }

}
