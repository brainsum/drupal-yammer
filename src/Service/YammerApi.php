<?php

namespace Drupal\yammer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\encrypt\Exception\EncryptException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use function base64_encode;
use function date;
use function file_put_contents;
use function json_decode;
use function reset;
use function str_replace;

/**
 * Class YammerApi.
 *
 * @package Drupal\yammer\Service
 */
class YammerApi {

  private static $baseUrl = 'https://www.yammer.com';
  private static $endpoints = [
    // @see: https://developer.yammer.com/docs/oauth-2
    'access_token' => '/oauth2/access_token.json',
    // @see: https://developer.yammer.com/docs/messagesfollowingjson
    'following_topics' => '/api/v1/messages/following.json',
    // https://developer.yammer.com/docs/messagesin_groupgroup_id
    'group_messages' => '/api/v1/messages/in_group/{{ group_id }}.json',
    // @todo: Implement for round 2.
    // https://developer.yammer.com/docs/subscriptions
    'subscribe' => '/api/v1/subscriptions',
    // https://developer.yammer.com/docs/messagesin_threadthreadidjson
    'thread_messages' => '/api/v1/messages/in_thread/{{ thread_id }}.json',
    // https://developer.yammer.com/docs/messagesliked_bycurrentjsonmessage_idid
    'like_message' => '/api/v1/messages/liked_by/current.json?message_id={{ message_id }}',
    // https://developer.yammer.com/docs/messages-json-post
    'send_message' => '/api/v1/messages.json',
  ];

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Config for the yammer module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   *
   * @see: yammer.settings
   */
  private $config;

  private $currentUser;

  private $tokenManager;

  private $tokenEncryption;

  private $dateFormatter;

  /**
   * Cache for request headers.
   *
   * @var array
   */
  private $requestHeaders;

  /**
   * Date format for formatting dates from Yammer.
   *
   * @var string
   */
  private $dateFormat;

  /**
   * YammerApi constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   HTTP Client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\yammer\Service\TokenManager $tokenManager
   *   The token manager.
   * @param \Drupal\yammer\Service\TokenEncryption $tokenEncryption
   *   Token encryption service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    Client $client,
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $currentUser,
    TokenManager $tokenManager,
    TokenEncryption $tokenEncryption,
    DateFormatterInterface $dateFormatter
  ) {
    $this->httpClient = $client;
    $this->config = $configFactory->get('yammer.settings');
    $this->dateFormat = $this->config->get('date_format');
    $this->currentUser = $currentUser;

    $this->tokenManager = $tokenManager;
    $this->tokenEncryption = $tokenEncryption;

    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Auth callback for yammer.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to 'redirect_path' (the page where we authenticated).
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function authCallback(Request $request): RedirectResponse {
    $code = $request->query->get('code', NULL);
    $redirectPath = $request->query->get('redirect_path', NULL);

    try {
      $client = $this->config->get('client');
      $response = $this->httpClient->get(static::$baseUrl . static::$endpoints['access_token'], [
        'query' => [
          'client_id' => $client['id'] ?? '',
          'client_secret' => $client['secret'] ?? '',
          'code' => $code,
        ],
      ]);
    }
    catch (GuzzleException $exception) {
      /* @todo: Handle properly.
       * - if response.body == "invalid Authorization code"
       */
      throw $exception;
    }

    $this->tokenManager->saveTokenResponse($this->currentUser, $response);

    // @todo: Maybe consider turning this into a contrib module.
    // - Allow storing multiple tokens, e.g 1 per network_id?
    // @todo: Double check if this is an OK path
    return new RedirectResponse($redirectPath);
  }

  /**
   * Replaces the placeholder in the group_messages endpoint URI with the ID.
   *
   * @param int $groupId
   *   The group ID.
   *
   * @return string
   *   The updated URI.
   */
  private function resolveGroupUri(int $groupId): string {
    return str_replace('{{ group_id }}', $groupId, static::$endpoints['group_messages']);
  }

  /**
   * Returns the yammer request headers.
   *
   * @return array
   *   The headers array.
   */
  private function requestHeaders(): array {
    if (!empty($this->requestHeaders)) {
      return $this->requestHeaders;
    }

    $token = $this->tokenManager->fetchServiceAccountToken();

    if ($token->token() === NULL) {
      // @todo: Log/throw exception.
      return [];
    }

    try {
      $decrypted = $this->tokenEncryption->decrypt($token->token());
    }
    catch (EncryptException $exception) {
      // @todo: Log/throw exception.
      return [];
    }

    $this->requestHeaders = [
      'Authorization' => "Bearer {$decrypted}",
    ];

    return $this->requestHeaders;
  }

  /**
   * Returns message data for the specified group.
   *
   * @param int $groupId
   *   ID of the Yammer Group.
   *
   * @return array
   *   The data.
   */
  public function groupMessagesData(int $groupId): array {
    // @todo: Cache data?
    try {
      $response = $this->httpClient->get(static::$baseUrl . $this->resolveGroupUri($groupId), [
        'query' => [
          'threaded' => TRUE,
          'limit' => 5,
        ],
        'headers' => $this->requestHeaders(),
      ]);
    }
    catch (GuzzleException $exception) {
      // @todo: Handle properly.
      return [];
    }

    $body = $response->getBody()->getContents();
    $data = json_decode($body, TRUE) ?? [];
    // @todo: Remove, debug only:
    $debugFileName = 'yammer-debug.group-' . $groupId . '.' . date('Y-m-d_H-i-s') . '.json';
    @file_put_contents(PrivateStream::basePath() . '/' . $debugFileName, $body);
    // @todo: End debug.
    return $this->processGroupMessagesData($data, $groupId);
  }

  /**
   * Process data for a specific group.
   *
   * @param array $data
   *   The raw data.
   * @param int $groupId
   *   The ID of the group.
   *
   * @return array
   *   The processed data.
   */
  public function processGroupMessagesData(array $data, int $groupId): array {
    if (empty($data['messages'])) {
      return [];
    }

    $messages = [];

    foreach ($data['messages'] as $message) {
      if ($message['sender_type'] !== 'user') {
        continue;
      }

      $userData = $this->resolveUser($data, $message['sender_id']);
      $threadData = $this->resolveThread($data, $message['thread_id']);

      $likes = $message['liked_by']['count'] ? (int) $message['liked_by']['count'] : 0;
      $replies = $threadData['replies'] ?? 0;
      $shares = $threadData['shares'] ?? 0;

      // @todo: notified_user_ids: array | This can be used for CC:.
      $messages[] = [
        'author_name' => $userData['name'] ?? 'Unknown',
        'author_url' => $userData['url'] ?? NULL,
        'author_image' => $userData['image'] ?? 'Unknown',
        'date' => $this->formatDate($message['created_at']),
        'body' => Markup::create($message['body']['rich']),
        'url' => $message['web_url'],
        'replied_to_id' => $message['replied_to_id'],
        'thread_id' => $message['thread_id'],
        'like_count' => $likes,
        'like_count_markup' => new PluralTranslatableMarkup($likes, '%count like', '%count likes', ['%count' => $likes]),
        'reply_count' => $replies,
        'reply_count_markup' => new PluralTranslatableMarkup($replies, '%count reply', '%count replies', ['%count' => $replies]),
        'share_count' => $shares,
        'share_count_markup' => new PluralTranslatableMarkup($shares, '%count share', '%count shares', ['%count' => $shares]),
        'attachments' => $this->resolveAttachments($message),
      ];
    }

    return [
      'group' => $this->resolveGroup($data, $groupId),
      'messages' => $messages,
    ];
  }

  /**
   * Resolves attachments for a message.
   *
   * @param array $message
   *   The message data.
   *
   * @return array
   *   The attachments data.
   */
  private function resolveAttachments(array $message): array {
    $attachmentData = $message['attachments'] ?? [];
    $attachments = [];

    foreach ($attachmentData as $attachment) {
      if ($attachment['type'] !== 'image') {
        // For now, ignore anything else. E.g ymodules, which can be
        // lots of things e.g polls.
        continue;
      }

      $name = $attachment['full_name'] ?? $attachment['name'] ?? 'Unknown';

      $attachments[] = [
        'type' => $attachment['type'],
        'name' => $name,
        'url' => $attachment['web_url'],
        'preview_url' => $attachment['preview_url'],
        'description' => empty($attachment['description']) ? $name : $attachment['description'],
        'thumbnail' => $attachment['thumbnail_url'] ?? NULL,
        'base64_data' => $this->imageData($attachment['preview_url']),
      ];
    }

    return $attachments;
  }

  /**
   * Returns the image data as base64 encoded data.
   *
   * @param string $url
   *   Image url.
   *
   * @return array|null
   *   The [type, data] array or NULL.
   */
  private function imageData($url): ?array {
    try {
      $response = $this->httpClient->get($url, [
        'headers' => $this->requestHeaders(),
      ]);
    }
    catch (GuzzleException $exception) {
      // @todo: Handle properly.
      return NULL;
    }

    $data = $response->getBody()->getContents();
    $headers = $response->getHeader('Content-Type');

    return [
      'type' => reset($headers) ?? 'image/png',
      'data' => base64_encode($data),
    ];
  }

  /**
   * Resolves a thread from the response.
   *
   * @param array $data
   *   The response data.
   * @param int $threadId
   *   The thread ID.
   *
   * @return array
   *   The resolved thread data.
   */
  private function resolveThread(array $data, int $threadId): array {
    $references = $data['references'] ?? [];

    foreach ($references as $reference) {
      if ($reference['type'] !== 'thread' || $reference['id'] !== $threadId) {
        continue;
      }

      // @note: updates includes the top level message.
      // @todo: Double-check that it doesn't include edits.
      return [
        'replies' => $reference['stats']['updates'] ? ((int) $reference['stats']['updates']) - 1 : 0,
        'shares' => $reference['stats']['shares'] ?? 0,
      ];

    }

    return [];
  }

  /**
   * Resolves a group from the response.
   *
   * @param array $data
   *   The response data.
   * @param int $groupId
   *   The group ID.
   *
   * @return array
   *   The resolved group data.
   */
  private function resolveGroup(array $data, int $groupId): array {
    $references = $data['references'] ?? [];

    foreach ($references as $reference) {
      if ($reference['type'] !== 'group' || $reference['id'] !== $groupId) {
        continue;
      }

      return [
        'url' => $reference['web_url'],
        'name' => $reference['full_name'],
        'image' => $reference['mugshot_url'],
      ];
    }

    return [];
  }

  /**
   * Resolves a user from the response.
   *
   * @param array $data
   *   The response data.
   * @param int $userID
   *   The ID of the user.
   *
   * @return array
   *   The resolved data.
   */
  private function resolveUser(array $data, int $userID): array {
    $references = $data['references'] ?? [];

    foreach ($references as $reference) {
      if ($reference['type'] !== 'user' || $reference['id'] !== $userID) {
        continue;
      }

      return [
        'url' => $reference['web_url'],
        'name' => $reference['full_name'],
        'image' => $reference['mugshot_url'],
      ];
    }

    return [];
  }

  /**
   * Formats a yammer date.
   *
   * @param string $date
   *   Yammer date.
   *
   * @return string
   *   The formatted date, or the "Unknown" string.
   */
  private function formatDate(string $date): string {
    $drupalDate = new DrupalDateTime($date);

    if ($drupalDate->hasErrors()) {
      return 'Unknown';
    }

    return $this->dateFormatter->format($drupalDate->getTimestamp(), $this->dateFormat);
  }

}
