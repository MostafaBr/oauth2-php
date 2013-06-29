<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\Oauth2\Model;

interface AccessTokenInterface extends ModelInterface
{
    /**
     * Get accessToken
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Get tokenType
     *
     * @return string
     */
    public function getTokenType();

    /**
     * Get clientId
     *
     * @return string
     */
    public function getClientId();

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Get expires
     *
     * @return integer
     */
    public function getExpires();

    /**
     * Get scope
     *
     * @return array
     */
    public function getScope();
}