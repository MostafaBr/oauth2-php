<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\Oauth2\Tests\Controller;

use Pantarei\Oauth2\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenControllerTest extends WebTestCase
{
    /**
     * @expectedException \Pantarei\Oauth2\Exception\InvalidRequestException
     */
    public function testExceptionNoGrantType()
    {
        $parameters = array(
            'code' => 'f0c68d250bcc729eb780a235371a9a55',
            'redirect_uri' => 'http://democlient2.com/redirect_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'http://democlient2.com/',
            'PHP_AUTH_PW' => 'demosecret2',
        );
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }

    /**
     * @expectedException \Pantarei\Oauth2\Exception\InvalidRequestException
     */
    public function testExceptionBadGrantType()
    {
        $parameters = array(
            'grant_type' => 'foo',
        );
        $server = array();
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testGoodAuthCode()
    {
        $parameters = array(
            'grant_type' => 'authorization_code',
            'code' => 'f0c68d250bcc729eb780a235371a9a55',
            'redirect_uri' => 'http://democlient2.com/redirect_uri',
        );
        $server = array(
            'PHP_AUTH_USER' => 'http://democlient2.com/',
            'PHP_AUTH_PW' => 'demosecret2',
        );
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));

        $parameters = array(
            'grant_type' => 'authorization_code',
            'code' => 'f0c68d250bcc729eb780a235371a9a55',
            'redirect_uri' => 'http://democlient2.com/redirect_uri',
            'client_id' => 'http://democlient2.com/',
            'client_secret' => 'demosecret2',
        );
        $server = array();
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }

    public function testGoodAuthCodeNoPassedRedirectUri()
    {
        $parameters = array(
            'grant_type' => 'authorization_code',
            'code' => 'f0c68d250bcc729eb780a235371a9a55',
            'client_id' => 'http://democlient2.com/',
            'client_secret' => 'demosecret2',
        );
        $server = array();
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }

    public function testGoodAuthCodeNoStoredRedirectUri()
    {
        // Insert client without redirect_uri.
        $modelManager =  $this->app['oauth2.model_manager.factory']->getModelManager('client');
        $model = $modelManager->createClient();
        $model->setClientId('http://democlient4.com/')
            ->setClientSecret('demosecret4');
        $modelManager->updateClient($model);

        $modelManager = $this->app['oauth2.model_manager.factory']->getModelManager('code');
        $model = $modelManager->createCode();
        $model->setCode('08fb55e26c84f8cb060b7803bc177af8')
            ->setClientId('http://democlient4.com/')
            ->setExpires(new \DateTime('+10 minutes'))
            ->setUsername('demousername4')
            ->setScope(array(
                'demoscope1',
            ));
        $modelManager->updateCode($model);

        $parameters = array(
            'grant_type' => 'authorization_code',
            'code' => '08fb55e26c84f8cb060b7803bc177af8',
            'redirect_uri' => 'http://democlient4.com/redirect_uri',
            'client_id' => 'http://democlient4.com/',
            'client_secret' => 'demosecret4',
        );
        $server = array();
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }

    public function testGoodClientCred()
    {
        $parameters = array(
            'grant_type' => 'client_credentials',
            'scope' => 'demoscope1 demoscope2 demoscope3',
        );
        $server = array(
            'PHP_AUTH_USER' => 'http://democlient1.com/',
            'PHP_AUTH_PW' => 'demosecret1',
        );
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }

    public function testGoodPassword()
    {
        $parameters = array(
            'grant_type' => 'password',
            'username' => 'demousername3',
            'password' => 'demopassword3',
            'scope' => 'demoscope1 demoscope2 demoscope3',
            'state' => 'demostate1',
        );
        $server = array(
            'PHP_AUTH_USER' => 'http://democlient3.com/',
            'PHP_AUTH_PW' => 'demosecret3',
        );
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }

    public function testGoodRefreshToken()
    {
        $parameters = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => '288b5ea8e75d2b24368a79ed5ed9593b',
            'scope' => 'demoscope1 demoscope2 demoscope3',
        );
        $server = array(
            'PHP_AUTH_USER' => 'http://democlient3.com/',
            'PHP_AUTH_PW' => 'demosecret3',
        );
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));

        $parameters = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => '288b5ea8e75d2b24368a79ed5ed9593b',
        );
        $server = array(
            'PHP_AUTH_USER' => 'http://democlient3.com/',
            'PHP_AUTH_PW' => 'demosecret3',
        );
        $client = $this->createClient();
        $crawler = $client->request('POST', '/token', $parameters, array(), $server);
        $this->assertNotNull(json_decode($client->getResponse()->getContent()));
    }
}