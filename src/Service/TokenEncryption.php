<?php

namespace Drupal\yammer\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;

/**
 * Class TokenEncryption.
 *
 * @package Drupal\yammer\Service
 */
class TokenEncryption {

  private const ENCRYPTION_PROFILE = 'yammer_token_encryption';

  /**
   * Encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  private $encryption;

  /**
   * Loaded encryption profile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface
   */
  private $encryptionProfile;

  /**
   * TokenEncryption constructor.
   *
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt
   *   The encryption service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EncryptServiceInterface $encrypt,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->encryption = $encrypt;
    $this->encryptionProfile = $entityTypeManager
      ->getStorage('encryption_profile')
      ->load(static::ENCRYPTION_PROFILE);
  }

  /**
   * Encrypts the token.
   *
   * @param string $token
   *   The raw token.
   *
   * @return string
   *   The encrypted token.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function encrypt(string $token): string {
    return $this->encryption->encrypt($token, $this->encryptionProfile);
  }

  /**
   * Decrypts the token.
   *
   * @param string $token
   *   The encrypted encoded token.
   *
   * @return string
   *   The decrypted token.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function decrypt(string $token): string {
    /* @todo: Jaros sent this.
     *
     * @phpcs:disable
     * @code
     * salt, data = to_decrypt.split "$$"
     *   len   = ActiveSupport::MessageEncryptor.key_len
     *   key   = ActiveSupport::KeyGenerator.new(Rails.application.secrets.secret_key_base).generate_key(salt, len)
     *   crypt = ActiveSupport::MessageEncryptor.new key
     *   crypt.decrypt_and_verify data
     * @endcode
     * @phpcs:enable
     */
    return $this->encryption->decrypt($token, $this->encryptionProfile);
  }

}
