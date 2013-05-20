<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\OAuth2\Extension\ResponseType;

use Pantarei\OAuth2\Entity\AccessTokens;
use Pantarei\OAuth2\Entity\RefreshTokens;
use Pantarei\OAuth2\Exception\InvalidRequestException;
use Pantarei\OAuth2\Extension\ResponseType;
use Pantarei\OAuth2\Util\ParameterUtils;
use Rhumsaa\Uuid\Uuid;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token response type implementation.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-4.2
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class TokenResponseType extends ResponseType
{
  /**
   * REQUIRED. Value MUST be set to "token".
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.2.1
   */
  private $response_type = 'token';

  /**
   * REQUIRED. The client identifier as described in Section 2.2.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.2.1
   */
  private $client_id = '';

  /**
   * OPTIONAL. As described in Section 3.1.2.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.2.1
   */
  private $redirect_uri = '';

  /**
   * OPTIONAL. The scope of the access request as described by Section 3.3.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.2.1
   */
  private $scope = array();

  /**
   * RECOMMENDED. An opaque value used by the client to maintain
   * state between the request and callback. The authorization
   * server includes this value when redirecting the user-agent back
   * to the client. The parameter SHOULD be used for preventing
   * cross-site request forgery as described in Section 10.12.
   *
   * @see http://tools.ietf.org/html/rfc6749#section-4.2.1
   */
  private $state = '';

  public function setClientId($client_id)
  {
    $this->client_id = $client_id;
    return $this;
  }

  public function getClientId()
  {
    return $this->client_id;
  }

  public function setRedirectUri($redirect_uri)
  {
    $this->redirect_uri = $redirect_uri;
    return $this;
  }

  public function getRedirectUri()
  {
    return $this->redirect_uri;
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

  public function setState($state)
  {
    $this->state = $state;
    return $this;
  }

  public function getState()
  {
    return $this->state;
  }

  public function __construct(Request $request, Application $app)
  {
    // REQUIRED: client_id.
    if (!$request->query->get('client_id')) {
      throw new InvalidRequestException();
    }

    // Validate and set client_id.
    if ($client_id = ParameterUtils::checkClientId($request, $app)) {
      $this->setClientId($client_id);
    }

    // Validate and set redirect_uri.
    if ($redirect_uri = ParameterUtils::checkRedirectUri($request, $app)) {
      $this->setRedirectUri($redirect_uri);
    }

    // Validate and set scope.
    if ($scope = ParameterUtils::checkScope($request, $app)) {
      $this->setScope($scope);
    }

    // Validate and set state.
    if ($state = $request->query->get('state')) {
      $this->setState($state);
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
    return 'response_type';
  }

  public function getName()
  {
    return $this->response_type;
  }
}
