<?php

require dirname(__DIR__) . '/vendor/autoload.php';

session_start();

// Create a server instance. Provide your trello app identifier and secret.
$server = new \League\OAuth1\Client\Server\Trello([
    'identifier'              => '',
    'secret'                  => '',
    'callbackUri'             => 'http://localhost:9000/',
    // The following can be used to set defaults for the server
    'scope'                   => 'read,write,account',
    'expiration'              => '1day',
    'name'                    => 'Trello App'
]);

// Create some basic UI help
function display($data, $fullWidth = true) {
    if ($fullWidth) {
        echo '<pre style="background: #efefef; margin: 1%; padding: 1%;">';
    } else {
        echo '<pre style="background: #efefef; margin: 1%; padding: 1%; width: 46%; float: left; clear: none;">';
    }

    var_dump($data);

    echo '</pre>';
};

// Obtain Temporary Credentials and User Authorization
if (!isset($_GET['oauth_token'], $_GET['oauth_verifier'])) {

    if (!isset($_GET['start'])) {
        echo '<a href="/?start=true">Login</a>';
    } else {

        // First part of OAuth 1.0 authentication is to
        // obtain Temporary Credentials.
        $temporaryCredentials = $server->getTemporaryCredentials();

        // Store credentials in the session, we'll need them later
        $_SESSION['temporary_credentials'] = serialize($temporaryCredentials);
        session_write_close();

        // Second part of OAuth 1.0 authentication is to obtain User Authorization
        // by redirecting the resource owner to the login screen on the server.
        // Create an authorization url.
        $authorizationUrl = $server->getAuthorizationUrl($temporaryCredentials);

        // Redirect the user to the authorization URL. The user will be redirected
        // to the familiar login screen on the server, where they will login to
        // their account and authorize your app to access their data.
        header('Location: ' . $authorizationUrl);
        exit;
    }

// Obtain Token Credentials
} else {
    try {

        // Add a link to reset the flow
        echo '<a href="/">Reset</a>';

        // Retrieve the temporary credentials we saved before.
        $temporaryCredentials = unserialize($_SESSION['temporary_credentials']);

        // We will now obtain Token Credentials from the server.
        $tokenCredentials = $server->getTokenCredentials(
            $temporaryCredentials,
            $_GET['oauth_token'],
            $_GET['oauth_verifier']
        );

        // We have token credentials, which we may use in authenticated
        // requests against the service provider's API. Let's look at them.
        display( $tokenCredentials->getIdentifier() );
        display( $tokenCredentials->getSecret() );


        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $server->getResourceOwner($tokenCredentials);

        // Let's view the details about the resource owner.
        display( $resourceOwner->toArray(), false );

        // The server provides a way to get an authenticated API request for
        // the service, using the access token; it returns an object conforming
        // to Psr\Http\Message\RequestInterface.
        //
        // Let's create a request to retrieve the details of the
        // resource owner's boards.
        $request = $server->getAuthenticatedRequest(
            'GET',
            'https://api.trello.com/1/members/me/boards',
            $tokenCredentials
        );

        $response = $server->getHttpClient()->send($request);

        $json = (string) $response->getBody();

        $payload = json_decode($json);

        // Let's view the details of the resource owner's boards.
        display( $payload, false );

    } catch (\League\OAuth1\Client\Exceptions\Exception $e) {

        // Failed to get the token credentials or user details.
        exit($e->getMessage());

    }

}
