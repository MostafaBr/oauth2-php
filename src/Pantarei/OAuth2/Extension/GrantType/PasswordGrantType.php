<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\OAuth2\Extension\GrantType;

use Pantarei\OAuth2\Entity\AccessTokens;
use Pantarei\OAuth2\Entity\RefreshTokens;
use Pantarei\OAuth2\Exception\InvalidRequestException;
use Pantarei\OAuth2\Extension\GrantType;
use Pantarei\OAuth2\Util\ParameterUtils;
use Rhumsaa\Uuid\Uuid;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Password grant type implementation.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-4.3.2
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class PasswordGrantType extends GrantType
{
  /**
   * REQUIRED. Value MUST be set to "password".
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.3.2
   */
  private $grant_type = 'password';

  /**
   * REQUIRED. The resource owner username.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.3.2
   */
  private $username = '';

  /**
   * REQUIRED. The resource owner password.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.3.2
   */
  private $password = '';

  /**
   * OPTIONAL. The scope of the access request as described by
   * Section 3.3.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.3.2
   */
  private $scope = array();

  /**
   * Need to fetch client_id from credential.
   */
  private $client_id = '';

  public function setUsername($username)
  {
    $this->username = $username;
    return $this;
  }

  public function getUsername()
  {
    return $this->username;
  }

  public function setPassword($password)
  {
    $this->password = $password;
    return $this;
  }

  public function getPassword()
  {
    return $this->password;
  }

  public function setScope($scope)
  {
    $this->scope = $scope;
    return $this;
  }

  public function getScope()
  {
    return $this->scope;
  }

  public function setClientId($client_id)
  {
    $this->client_id = $client_id;
    return $this;
  }

  public function getClientId()
  {
    return $this->client_id;
  }

  public function __construct(Request $request, Application $app) {
    // REQUIRED: username, password.
    if (!$request->request->get('username') || !$request->request->get('password')) {
      throw new InvalidRequestException();
    }

    // Validate and set client_id.
    if ($client_id = ParameterUtils::checkClientId($request, $app, 'POST')) {
      $this->setClientId($client_id);
    }

    // Validate and set username.
    if ($username = ParameterUtils::checkUsername($request, $app)) {
      $this->setUsername($username);

      // Validate and set password.
      if ($password = ParameterUtils::checkPassword($request, $app)) {
        $this->setPassword($password);
      }
    }

    // Validate and set scope.
    if ($scope = ParameterUtils::checkScope($request, $app, 'POST')) {
      $this->setScope($scope);
    }
  }

  public function getResponse(Request $request, Application $app)
  {
    $access_token = new AccessTokens();
    $access_token->setAccessToken(md5(Uuid::uuid4()))
      ->setTokenType('bearer')
      ->setClientId($this->getClientId())
      ->setUsername('')
      ->setExpires(time() + 3600)
      ->setScope($this->getScope());
    $app['oauth2.orm']->persist($access_token);
    $app['oauth2.orm']->flush();

    $refresh_token = new RefreshTokens();
    $refresh_token->setRefreshToken(md5(Uuid::uuid4()))
      ->setTokenType('bearer')
      ->setClientId($this->getClientId())
      ->setUsername('')
      ->setExpires(time() + 86400)
      ->setScope($this->getScope());
    $app['oauth2.orm']->persist($refresh_token);
    $app['oauth2.orm']->flush();

    $parameters = array(
      'access_token' => $access_token->getAccessToken(),
      'token_type' => $access_token->getTokenType(),
      'expires_in' => $access_token->getExpires() - time(),
      'refresh_token' => $refresh_token->getRefreshToken(),
      'scope' => implode(' ', $this->getScope()),
    );
    $headers = array(
      'Cache-Control' => 'no-store',
      'Pragma' => 'no-cache',
    );
    $response = JsonResponse::create(array_filter($parameters), 200, $headers);

    return $response;
  }

  public function getParent()
  {
    return 'grant_type';
  }

  public function getName()
  {
    return $this->grant_type;
  }
}
