<?php

namespace League\OAuth1\Client\Server;

use League\OAuth1\Client\Exceptions\IdentityProviderException;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Psr\Http\Message\ResponseInterface;

class Trello extends AbstractServer
{
    /**
     * Requested token expiration
     *
     * @var string
     */
    protected $expiration;

    /**
     * Application name displayed to authenticating user
     *
     * @var string
     */
    protected $name;

    /**
     * Requested token scope
     *
     * @var string
     */
    protected $scope;

    /**
     * Build authorization query parameters.
     *
     * @param  array $options
     *
     * @return string
     */
    protected function buildAuthorizationQueryParameters(array $options = array())
    {
        $params = array(
            'response_type' => 'fragment',
            'scope' => isset($options['scope']) ? $options['scope'] : $this->scope,
            'expiration' => isset($options['expiration']) ? $options['expiration'] : $this->expiration,
            'name' => isset($options['name']) ? $options['name'] : $this->name,
        );

        return http_build_query(array_filter($params));
    }

    /**
     * Checks a provider response for errors.
     *
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     *
     * @return void
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        //
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  TokenCredentials $token
     *
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, TokenCredentials $tokenCredentials)
    {
        return new TrelloResourceOwner($response);
    }

    /**
     * Creates an authenticated query string and merges with a given query string,
     * if provided.
     *
     * @param  TokenCredentials  $tokenCredentials
     * @param  string            $query
     *
     * @return string
     */
    protected function getAuthenticatedQueryString(TokenCredentials $tokenCredentials, $query = '')
    {
        $query = parse_str($query);
        $query['key'] = (string) $this->clientCredentials;
        $query['token'] = (string) $tokenCredentials;

        return http_build_query($query);
    }

    /**
     * Creates a new authenticated request.
     *
     * @param  string            $method
     * @param  string            $url
     * @param  TokenCredentials  $tokenCredentials
     *
     * @return Psr\Http\Message\RequestInterface
     */
    public function getAuthenticatedRequest($method, $url, TokenCredentials $tokenCredentials)
    {
        $request = parent::getAuthenticatedRequest($method, $url, $tokenCredentials);

        $uri = $request->getUri()->withQuery(
            $this->getAuthenticatedQueryString(
                $tokenCredentials,
                $request->getUri()->getQuery()
            )
        );

        return $request->withUri($uri);
    }

    /**
     * Gets the URL for redirecting the resource owner to authorize the client.
     *
     * @return string
     */
    protected function getBaseAuthorizationUrl(array $options = array())
    {
        return 'https://trello.com/1/OAuthAuthorizeToken?'.
            $this->buildAuthorizationQueryParameters($options);
    }

    /**
     * Gets the URL for retrieving temporary credentials.
     *
     * @return string
     */
    protected function getBaseTemporaryCredentialsUrl()
    {
        return 'https://trello.com/1/OAuthGetRequestToken';
    }

    /**
     * Gets the URL retrieving token credentials.
     *
     * @return string
     */
    protected function getBaseTokenCredentialsUrl()
    {
        return 'https://trello.com/1/OAuthGetAccessToken';
    }

    /**
     * Gets the URL for retrieving user details.
     *
     * @return string
     */
    protected function getResourceOwnerDetailsUrl(TokenCredentials $tokenCredentials)
    {
        return 'https://trello.com/1/members/me?'.$this->getAuthenticatedQueryString($tokenCredentials);
    }
}
