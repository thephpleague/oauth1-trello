<?php namespace League\OAuth1\Client\Test\Server;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use League\OAuth1\Client\Server\Trello;
use League\OAuth1\Client\Server\TrelloResourceOwner;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;

class TrelloTest extends \PHPUnit_Framework_TestCase
{
    protected $server;

    protected function setUp()
    {
        $this->server = new \League\OAuth1\Client\Server\Trello([
            'identifier' => 'mock_identifier',
            'secret' => 'mock_secret',
            'callbackUri' => 'http://example.com/',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testGetAuthorizationUrlWithDefaults()
    {
        $options = [
            'identifier' => 'mock_identifier',
            'secret' => 'mock_secret',
            'callbackUri' => 'http://example.com/',
        ];

        $parameters = [
            'response_type' => 'fragment',
            'scope' => 'read',
            'expiration' => '1day',
            'name' => 'Trello App'
        ];

        $server = new \League\OAuth1\Client\Server\Trello(array_merge($options, $parameters));


        $credentials = m::mock(TemporaryCredentials::class);
        $credentials->shouldReceive('getIdentifier')->andReturn('foo');

        $url = $server->getAuthorizationUrl($credentials);

        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('oauth_token', $query);

        array_walk($parameters, function ($value, $key) use ($query) {
            $this->assertArrayHasKey($key, $query);
            $this->assertEquals($value, $query[$key]);
        });

        $this->assertEquals('/1/OAuthAuthorizeToken', $uri['path']);
    }

    public function testGetAuthorizationUrlWithOptions()
    {
        $parameters = [
            'response_type' => 'fragment',
            'scope' => 'read',
            'expiration' => '1day',
            'name' => 'Trello App'
        ];
        $credentials = m::mock(TemporaryCredentials::class);
        $credentials->shouldReceive('getIdentifier')->andReturn('foo');

        $url = $this->server->getAuthorizationUrl($credentials, $parameters);

        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('oauth_token', $query);

        array_walk($parameters, function ($value, $key) use ($query) {
            $this->assertArrayHasKey($key, $query);
            $this->assertEquals($value, $query[$key]);
        });

        $this->assertEquals('/1/OAuthAuthorizeToken', $uri['path']);
    }

    public function testGetTemporaryCredentials()
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('oauth_token=temporarycredentialsidentifier&oauth_token_secret=temporarycredentialssecret&oauth_callback_confirmed=true');

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->with(m::on(function($request) {
            $this->assertEquals('https', $request->getUri()->getScheme());
            $this->assertEquals('trello.com', $request->getUri()->getHost());
            $this->assertEquals('/1/OAuthGetRequestToken', $request->getUri()->getPath());

            return true;
        }))->once()->andReturn($response);

        $temporaryCredentials = $this->server->setHttpClient($client)
            ->getTemporaryCredentials();

        $this->assertInstanceOf(TemporaryCredentials::class, $temporaryCredentials);
        $this->assertEquals('temporarycredentialsidentifier', (string) $temporaryCredentials);
        $this->assertEquals('temporarycredentialssecret', $temporaryCredentials->getSecret());
    }

    public function testGetAccessToken()
    {
        $temporaryIdentifier = 'foo';
        $verifier = 'bar';

        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('oauth_token=tokencredentialsidentifier&oauth_token_secret=tokencredentialssecret');

        $temporaryCredentials = m::mock(TemporaryCredentials::class);
        $temporaryCredentials->shouldReceive('getIdentifier')->andReturn('foo');
        $temporaryCredentials->shouldReceive('getSecret')->andReturn('bar');
        $temporaryCredentials->shouldReceive('checkIdentifier')->with($temporaryIdentifier);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->with(m::on(function($request) {
            $this->assertEquals('https', $request->getUri()->getScheme());
            $this->assertEquals('trello.com', $request->getUri()->getHost());
            $this->assertEquals('/1/OAuthGetAccessToken', $request->getUri()->getPath());

            return true;
        }))->once()->andReturn($response);

        $tokenCredentials = $this->server->setHttpClient($client)
            ->getTokenCredentials($temporaryCredentials, $temporaryIdentifier, $verifier);

        $this->assertInstanceOf(TokenCredentials::class, $tokenCredentials);
        $this->assertEquals('tokencredentialsidentifier', (string) $tokenCredentials);
        $this->assertEquals('tokencredentialssecret', $tokenCredentials->getSecret());
    }

    public function testResourceOwnerData()
    {
        $userJson = file_get_contents(dirname(__DIR__).'/user.json');
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn($userJson);
        $expectedUser = json_decode($userJson, true);

        $tokenCredentials = m::mock(TokenCredentials::class);
        $tokenCredentials->shouldReceive('getIdentifier')->andReturn('foo');
        $tokenCredentials->shouldReceive('getSecret')->andReturn('bar');

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('send')->with(m::on(function($request) {
            $this->assertEquals('https', $request->getUri()->getScheme());
            $this->assertEquals('trello.com', $request->getUri()->getHost());
            $this->assertEquals('/1/members/me', $request->getUri()->getPath());

            return true;
        }))->once()->andReturn($response);

        $resourceOwner = $this->server->setHttpClient($client)
            ->getResourceOwner($tokenCredentials);

        $this->assertInstanceOf(TrelloResourceOwner::class, $resourceOwner);
        $this->assertEquals($expectedUser['id'], $resourceOwner->getId());
        $this->assertEquals($expectedUser, $resourceOwner->toArray());
    }

    public function testGetAuthenticatedRequest()
    {
        $method = 'foo';
        $url = 'http://foo.bar/path';
        $token = uniqid();

        $tokenCredentials = new TokenCredentials($token, 'secret');

        $request = $this->server->getAuthenticatedRequest($method, $url, $tokenCredentials);

        $this->assertEquals(strtoupper($method), $request->getMethod());
        $this->assertEquals('http', $request->getUri()->getScheme());
        $this->assertEquals('foo.bar', $request->getUri()->getHost());
        $this->assertEquals('/path', $request->getUri()->getPath());
        $this->assertContains('token=' . $token, $request->getUri()->getQuery());
        $this->assertContains('key=' . 'mock_identifier', $request->getUri()->getQuery());
    }
}
