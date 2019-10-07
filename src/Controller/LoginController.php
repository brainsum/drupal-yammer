<?php

namespace Drupal\yammer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\yammer\Render\LoginButton;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LoginController.
 *
 * @package Drupal\yammer\Controller
 */
class LoginController extends ControllerBase {

  /**
   * Config for the yammer module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   *
   * @see: yammer.settings
   */
  private $yammerConfig;

  /**
   * LoginController constructor.
   */
  public function __construct() {
    $this->yammerConfig = $this->config('yammer.settings');
  }

  /**
   * Returns the login button.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Render array.
   */
  public function login(Request $request): array {
    $redirectUri = $this->getUrlGenerator()->generateFromRoute(
      'yammer.auth_success',
      [
        'query' => [
          'redirect_path' => $request->getPathInfo(),
        ],
      ],
      [
        'absolute' => TRUE,
      ],
      TRUE
    )->getGeneratedUrl();

    return (new LoginButton())->build($this->yammerConfig->get('client')['id'] ?? '', $redirectUri);
  }

}
