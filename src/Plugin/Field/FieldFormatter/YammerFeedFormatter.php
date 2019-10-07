<?php

namespace Drupal\yammer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\yammer\Service\YammerApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'yammer_feed_formatter' formatter.
 *
 * @FieldFormatter (
 *   id = "yammer_feed_formatter",
 *   label = @Translation("Yammer feed"),
 *   field_types = {
 *     "yammer_feed"
 *   }
 * )
 */
class YammerFeedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The yammer API.
   *
   * @var \Drupal\yammer\Service\YammerApi
   */
  private $yammerApi;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('yammer.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    YammerApi $yammerApi
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->yammerApi = $yammerApi;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    // @todo: Replace with an AJAX frontend [mb react components?].
    if ($items->isEmpty()) {
      return [];
    }

    // @todo: Check if service account for yammer has a token.
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->group_id)) {
        $yammerData = $this->yammerApi->groupMessagesData($item->group_id) ?? [];

        if (empty($yammerData['group']['url'])) {
          $elements[$delta] = [
            '#markup' => new TranslatableMarkup("The Yammer widget can't be displayed, please check the group setup in the application."),
          ];
          continue;
        }

        $elements[$delta] = [
          '#theme' => 'yammer_feed',
          '#content' => [
            'group_id' => $item->group_id,
            'group_name' => $yammerData['group']['name'] ?? 'Unknown',
            'group_url' => $yammerData['group']['url'] ?? NULL,
            'messages' => $yammerData['messages'] ?? [],
          ],
        ];
      }
    }

    return $elements;
  }

}
