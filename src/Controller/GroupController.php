<?php

namespace Drupal\yammer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\yammer\Service\YammerApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupController.
 *
 * @package Drupal\yammer\Controller
 */
class GroupController extends ControllerBase {

  /**
   * The yammer API.
   *
   * @var \Drupal\yammer\Service\YammerApi
   */
  private $yammerApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yammer.api')
    );
  }

  /**
   * GroupController constructor.
   *
   * @param \Drupal\yammer\Service\YammerApi $yammerApi
   *   The yammer API.
   */
  public function __construct(
    YammerApi $yammerApi
  ) {
    $this->yammerApi = $yammerApi;
  }

  /**
   * Render a Yammer Group.
   *
   * @param int $groupId
   *   The Yammer group ID.
   *
   * @return array
   *   The render array.
   */
  public function group(int $groupId): array {
    $data = $this->yammerApi->groupMessagesData($groupId) ?? [];

    return [
      '#theme' => 'yammer_feed',
      '#content' => [
        'group_id' => $groupId,
        'group_name' => $data['group']['name'] ?? 'Unknown',
        'group_url' => $data['group']['url'] ?? NULL,
        'messages' => $data['messages'] ?? [],
      ],
    ];
  }

}
