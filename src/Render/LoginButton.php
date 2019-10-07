<?php

namespace Drupal\yammer\Render;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class LoginButton.
 *
 * @package Drupal\yammer\Render
 */
class LoginButton {

  /**
   * Returns the render array for the Login button.
   *
   * @param string $clientId
   *   Client ID.
   * @param string $redirectUri
   *   Redirect URI after successful login.
   *
   * @return array
   *   Render array.
   */
  public function build(string $clientId, string $redirectUri): array {
    // $clientId: Yammer app ID.
    // $redirectUri: yammer.auth_success route
    return [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Sign in with Yammer'),
      '#url' => Url::fromUri('https://www.yammer.com/oauth2/authorize', [
        'query' => [
          'client_id' => $clientId,
          'response_type' => 'code',
          'redirect_uri' => $redirectUri,
        ],
      ]),
      '#attributes' => [
        'class' => [
          'btn',
          'button',
          'button--yammer-login',
          'use-ajax',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
        'target' => '_blank',
        'rel' => 'nofollow noopener noreferrer',
      ],
      '#attached' => [
        'library' => [
          'yammer/feed',
        ],
        'drupalSettings' => [],
      ],
    ];
  }

}
