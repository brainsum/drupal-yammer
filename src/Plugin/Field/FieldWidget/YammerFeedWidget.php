<?php

namespace Drupal\yammer\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'yammer_feed_formatter' widget.
 *
 * @FieldWidget(
 *   id = "yammer_feed_widget",
 *   label = @Translation("Yammer feed widget"),
 *   field_types = {
 *     "yammer_feed"
 *   }
 * )
 */
class YammerFeedWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label_hidden' => FALSE,
    ] + parent::defaultSettings();
  }

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
    $element['group_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Yammer group ID'),
      '#description' => $this->t("Set to 0 if you don't want to fill out."),
      '#default_value' => $items[$delta]->group_id ?? 0,
      '#size' => 15,
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if (
      !$this->getSetting('label_hidden')
      && $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() === 1
    ) {
      $element += [
        '#type' => 'fieldset',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['label_hidden'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Hide the field label.'),
      '#default_value' => $this->getSetting('label_hidden'),
      '#weight' => -1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $labelHidden = $this->getSetting('label_hidden');
    $summary[] = new TranslatableMarkup('Hide label: @label_hidden', [
      '@label_hidden' => $labelHidden ? new TranslatableMarkup('Yes') : new TranslatableMarkup('No'),
    ]);
    return $summary;
  }

}
