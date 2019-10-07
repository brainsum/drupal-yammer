<?php

namespace Drupal\yammer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'yammer_user_data_formatter' formatter.
 *
 * @FieldFormatter (
 *   id = "yammer_user_data_formatter",
 *   label = @Translation("Yammer user data"),
 *   field_types = {
 *     "yammer_user_data"
 *   }
 * )
 */
class YammerUserDataFormatter extends FormatterBase {

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

}
