<?php

namespace Drupal\yammer\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\yammer\Model\YammerToken;
use Psr\Http\Message\ResponseInterface;
use function array_filter;
use function reset;

/**
 * Class TokenManager.
 *
 * @package Drupal\yammer\Service
 */
class TokenManager {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  private $userStorage;

  /**
   * Config for the yammer module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   *
   * @see: yammer.settings
   */
  private $yammerConfig;

  /**
   * Token factory.
   *
   * @var \Drupal\yammer\Service\YammerTokenFactory
   */
  private $tokenFactory;

  /**
   * TokenManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\yammer\Service\YammerTokenFactory $tokenFactory
   *   The token factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    YammerTokenFactory $tokenFactory
  ) {
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->yammerConfig = $configFactory->get('yammer.settings');
    $this->tokenFactory = $tokenFactory;
  }

  /**
   * Save TokenResponse for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveTokenResponse(
    AccountInterface $account,
    ResponseInterface $response
  ): void {
    $token = $this->tokenFactory->createFromResponse($account, $response);
    $this->saveAccountToken($account, $token);
  }

  /**
   * Saves token for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Drupal\yammer\Model\YammerToken $token
   *   The token info.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveAccountToken(AccountInterface $account, YammerToken $token): void {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($account->id());
    $this->saveUserTokenInfo($user, $token);
  }

  /**
   * Save token info for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param \Drupal\yammer\Model\YammerToken $token
   *   The token info.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveUserTokenInfo(UserInterface $user, YammerToken $token): void {
    $user->set('yammer_info', [
      'token' => $token->token(),
      'expires_at' => $token->expiresAt(),
    ]);
    $this->userStorage->save($user);
  }

  /**
   * Returns the token info for the account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\yammer\Model\YammerToken
   *   The user token.
   */
  public function fetchAccountToken(AccountInterface $account): YammerToken {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($account->id());
    return $this->fetchUserToken($user);
  }

  /**
   * Returns the token info for the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return \Drupal\yammer\Model\YammerToken
   *   The user token.
   */
  public function fetchUserToken(UserInterface $user): YammerToken {
    return $this->tokenFactory->createFromUser($user);
  }

  /**
   * Loads the service account.
   *
   * @return \Drupal\user\UserInterface|null
   *   The service account or NULL.
   */
  public function loadServiceAccount(): ?UserInterface {
    $info = array_filter($this->yammerConfig->get('service_account') ?? [], static function ($item) {
      return !empty($item);
    });

    /** @var \Drupal\user\UserInterface[] $serviceAccountUser */
    $serviceAccountUser = $this->userStorage->loadByProperties($info);

    if (empty($serviceAccountUser)) {
      return NULL;
    }

    return reset($serviceAccountUser);
  }

  /**
   * Fetches the Token for the service account.
   *
   * @return \Drupal\yammer\Model\YammerToken
   *   The token.
   */
  public function fetchServiceAccountToken(): YammerToken {
    $user = $this->loadServiceAccount();

    if ($user === NULL) {
      return new YammerToken([]);
    }

    return $this->fetchUserToken($user);
  }

}
