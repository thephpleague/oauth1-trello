<?php namespace League\OAuth1\Client\Test\Server;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use League\OAuth1\Client\Server\Trello;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
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

    public function testGetAuthorizationUrl()
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

    public function testGetAccessToken()
    {
        $temporaryIdentifier = 'foo';
        $verifier = 'bar';

        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn('oauth_token=temporarycredentialsidentifier&oauth_token_secret=temporarycredentialssecret&oauth_callback_confirmed=true');
        //$response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $temporaryCredentials = m::mock(TemporaryCredentials::class);
        $temporaryCredentials->shouldReceive('getIdentifier')->andReturn('foo');
        $temporaryCredentials->shouldReceive('checkIdentifier')->with($temporaryIdentifier);

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('post')->with('https://trello.com/1/OAuthGetRequestToken', m::on(function($headers) {
            $this->assertTrue(isset($headers['Authorization']));
            // OAuth protocol specifies a strict number of
            // headers should be sent, in the correct order.
            // We'll validate that here.
            $pattern = '/OAuth oauth_consumer_key=".*?", oauth_nonce="[a-zA-Z0-9]+", oauth_signature_method="HMAC-SHA1", oauth_timestamp="\d{10}", oauth_version="1.0", oauth_callback="'.preg_quote('http%3A%2F%2Fapp.dev%2F', '/').'", oauth_signature=".*?"/';
            $matches = preg_match($pattern, $headers['Authorization']);
            $this->assertEquals(1, $matches, 'Asserting that the authorization header contains the correct expression.');
            return true;
        }))->once()->andReturn($response);

        $tokenCredentials = $this->server->setHttpClient($client)
            ->getTokenCredentials($temporaryCredentials, $temporaryIdentifier, $verifier);
    }

    public function testResourceOwnerData()
    {
        //
    }

    /**
     * @expectedException League\OAuth1\Client\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        //
    }

    public function testGetAuthenticatedRequest()
    {
        //
    }
}
