<?php

namespace Drupal\yammer\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'yammer_user_data_widget' widget.
 *
 * @FieldWidget(
 *   id = "yammer_user_data_widget",
 *   label = @Translation("Yammer user data widget"),
 *   field_types = {
 *     "yammer_user_data"
 *   }
 * )
 */
class YammerUserDataWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    return [];
  }

}
