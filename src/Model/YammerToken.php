<?php

namespace Drupal\yammer\Model;

use Drupal\user\UserInterface;

/**
 * Class YammerToken.
 *
 * @package Drupal\yammer\Model
 */
class YammerToken {

  /**
   * The Drupal user.
   *
   * @var \Drupal\user\UserInterface|null
   */
  private $drupalUser;

  /**
   * The Yammer ID of the user.
   *
   * @var int|null
   */
  private $yammerUserId;

  /**
   * The Yammer network name.
   *
   * @var string|null
   */
  private $yammerNetworkName;

  /**
   * The Yammer network ID.
   *
   * @var int|null
   */
  private $yammerNetworkId;

  /**
   * The encrypted token.
   *
   * @var string|null
   */
  private $token;

  /**
   * The expiry date timestamp of the token.
   *
   * @var int|null
   */
  private $expiresAt;

  /**
   * YammerToken constructor.
   *
   * @param array $tokenInfo
   *   Token info array.
   */
  public function __construct(array $tokenInfo) {
    $this->token = $tokenInfo['token'];
    $this->expiresAt = $tokenInfo['expires_at'];
    $this->yammerNetworkName = $tokenInfo['network_name'];
    $this->yammerNetworkId = $tokenInfo['network_id'];
    $this->yammerUserId = $tokenInfo['user_id'];
    $this->drupalUser = $tokenInfo['drupal_user'];
  }

  /**
   * Returns the token.
   *
   * @return string|null
   *   The token.
   *
   * @note: This should be encrypted asap.
   */
  public function token(): ?string {
    return $this->token;
  }

  /**
   * Returns the expiry date as a unix timestamp.
   *
   * @return int|null
   *   The timestamp or NULL, if it doesn't expire.
   */
  public function expiresAt(): ?int {
    return $this->expiresAt;
  }

  /**
   * Returns the Drupal user.
   *
   * @return \Drupal\user\UserInterface|null
   *   The Drupal user.
   */
  public function drupalUser(): ?UserInterface {
    return $this->drupalUser;
  }

  /**
   * Returns the Yammer user ID.
   *
   * @return int|null
   *   The ID.
   */
  public function yammerUserId(): ?int {
    return $this->yammerUserId;
  }

  /**
   * Returns the Yammer network ID.
   *
   * @return int|null
   *   The network ID.
   */
  public function yammerNetworkId(): ?int {
    return $this->yammerNetworkId;
  }

  /**
   * Returns the Yammer network name.
   *
   * @return string|null
   *   The network name.
   */
  public function yammerNetworkName(): ?string {
    return $this->yammerNetworkName;
  }

}
