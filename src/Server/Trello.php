<?php

namespace League\OAuth1\Client\Server;

use League\OAuth1\Client\Exceptions\IdentityProviderException;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Psr\Http\Message\ResponseInterface;

class Trello extends AbstractServer
{
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
            'scope' => '',
            'expiration' => '',
            'name' => '',
        );

        return http_build_query($params);
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
     * Gets the URL for redirecting the resource owner to authorize the client.
     *
     * @return string
     */
    protected function getBaseAuthorizationUrl()//array $options = array())
    {
        return 'https://trello.com/1/OAuthAuthorizeToken?'.
            $this->buildAuthorizationQueryParameters();
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
    protected function getResourceOwnerDetailsUrl()//TokenCredentials $tokenCredentials)
    {
        return 'https://trello.com/1/members/me?key='.$this->clientCredentials->getIdentifier().
            '&token='.$tokenCredentials->getIdentifier();
    }
}
