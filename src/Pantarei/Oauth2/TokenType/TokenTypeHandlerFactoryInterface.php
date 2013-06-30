<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\Oauth2\TokenType;

/**
 * Oauth2 token type handler factory interface.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
interface TokenTypeHandlerFactoryInterface
{
    /**
     * Gets a stored token type handler.
     *
     * @param string $type
     *   Type of token type handler, as refer to RFC6749.
     *
     * @return GrantTypeHandlerInterface
     *   The stored token type handler.
     *
     * @throw UnsupportedGrantTypeException
     *   If supplied token type not found.
     */
    public function getTokenTypeHandler($type = null);
}
