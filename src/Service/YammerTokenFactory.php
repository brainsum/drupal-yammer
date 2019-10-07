<?php

namespace Drupal\yammer\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\user\UserInterface;
use Drupal\yammer\Model\YammerToken;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Class YammerTokenFactory.
 *
 * @package Drupal\yammer\Service
 */
class YammerTokenFactory {

  /**
   * Token encryption.
   *
   * @var \Drupal\yammer\Service\tokenEncryption
   */
  private $tokenEncryption;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  private $userStorage;

  /**
   * YammerTokenFactory constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\yammer\Service\TokenEncryption $tokenEncryption
   *   The token manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TokenEncryption $tokenEncryption
  ) {
    $this->userStorage = $entityTypeManager
      ->getStorage('user');
    $this->tokenEncryption = $tokenEncryption;
  }

  /**
   * Creates a YammerToken.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The Drupal account to which the token belongs.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP Response from Yammer.
   *
   * @return \Drupal\yammer\Model\YammerToken
   *   The instance.
   *
   * @throws \RuntimeException
   */
  public function createFromResponse(
    AccountInterface $account,
    ResponseInterface $response
  ): YammerToken {
    $body = $response->getBody()->getContents();
    $parsed = json_decode($body, TRUE);

    try {
      $token = $this->tokenEncryption->encrypt((string) $parsed['access_token']['token']);
    }
    catch (EncryptException $exception) {
      throw new RuntimeException('The Yammer access token could not be encrypted.');
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($account->id());

    $expiresAtDate = $parsed['access_token']['expires_at']
      ? (new DrupalDateTime((string) $parsed['access_token']['expires_at']))->getTimestamp()
      : NULL;

    return new YammerToken([
      'token' => $token,
      'expires_at' => $expiresAtDate,
      'network_name' => (string) $parsed['access_token']['network_name'],
      'network_id' => (int) $parsed['access_token']['network_id'],
      'user_id' => (int) $parsed['access_token']['user_id'],
      'drupal_user' => $user,
    ]);
  }

  /**
   * Create a YammerToken instance from a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\yammer\Model\YammerToken
   *   The token.
   */
  public function createFromAccount(AccountInterface $account): YammerToken {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($account->id());
    return $this->createFromUser($user);
  }

  /**
   * Create from field.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return \Drupal\yammer\Model\YammerToken
   *   The token.
   */
  public function createFromUser(UserInterface $user): YammerToken {
    if (!$user->hasField('yammer_info')) {
      return new YammerToken([]);
    }

    /** @var \Drupal\yammer\Plugin\Field\FieldType\YammerUserData $field */
    $field = $user->get('yammer_info');

    if ($field->isEmpty()) {
      // @todo: Throw exception instead?
      return new YammerToken([]);
    }

    $values = $field->getValue()[0] ?? [];

    try {
      return new YammerToken([
        'token' => $values['token'] ?? NULL,
        'expires_at' => $values['expires_at'] ?? NULL,
        'network_name' => '',
        'network_id' => '',
        'user_id' => '',
        'drupal_user' => $user,
      ]);
    }
    catch (MissingDataException $exception) {
      return new YammerToken([]);
    }
  }

}
