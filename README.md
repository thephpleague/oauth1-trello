# Trello Provider for OAuth 1.0 Client
[![Latest Version](https://img.shields.io/github/release/thephpleague/oauth1-trello.svg?style=flat-square)](https://github.com/thephpleague/oauth1-trello/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/thephpleague/oauth1-trello/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/oauth1-trello)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/oauth1-trello.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/oauth1-trello/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/oauth1-trello.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/oauth1-trello)
[![Total Downloads](https://img.shields.io/packagist/dt/league/oauth1-trello.svg?style=flat-square)](https://packagist.org/packages/league/oauth1-trello)

This package provides Trello OAuth 1.0 support for the PHP League's [OAuth 1.0 Client](https://github.com/thephpleague/oauth1-client).

## Installation

To install, use composer:

```
composer require league/oauth1-trello
```

## Usage

Usage is the same as The League's OAuth client, using `\League\OAuth1\Client\Server\Trello` as the server.

### Authenticating with OAuth 1.0

```php
// Create a server instance.
$server = new \League\OAuth1\Client\Server\Trello([
    'identifier'              => 'your-identifier',
    'secret'                  => 'your-secret',
    'callbackUri'             => 'http://your-callback-uri/',
    // The following can be used to set defaults for the server
    'scope'                   => 'read',
    'expiration'              => '1day',
    'name'                    => 'Trello App'
]);

// Obtain Temporary Credentials and User Authorization
if (!isset($_GET['oauth_token'], $_GET['oauth_verifier'])) {

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

// Obtain Token Credentials
} else {

    try {

        // Retrieve the temporary credentials we saved before.
        $temporaryCredentials = unserialize($_SESSION['temporary_credentials']);

        // We will now obtain Token Credentials from the server.
        $tokenCredentials = $server->getTokenCredentials(
            $temporaryCredentials,
            $_GET['oauth_token'],
            $_GET['oauth_verifier']
        );

        // We have token credentials, which we may use in authenticated
        // requests against the service provider's API.
        echo $tokenCredentials->getIdentifier() . "\n";
        echo $tokenCredentials->getSecret() . "\n";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $server->getResourceOwner($tokenCredentials);

        var_export($resourceOwner->toArray());

        // The server provides a way to get an authenticated API request for
        // the service, using the access token; it returns an object conforming
        // to Psr\Http\Message\RequestInterface.
        $request = $server->getAuthenticatedRequest(
            'GET',
            'http://your.service/endpoint',
            $tokenCredentials
        );

    } catch (\League\OAuth1\Client\Exceptions\Exception $e) {

        // Failed to get the token credentials or user details.
        exit($e->getMessage());

    }

}
```

### Configuring your server

In order to complete the authorization flow with your user, you will need to provide three additional pieces of information.

| name | description |
| ---- | ----------- |
| `scope` | Scope informs the Trello service about which permissions you are requesting on behalf of your user. `read` or `read,write` |
| `expiration` | Expiration informs the Trello service about how long you are requesting this permissions. `1day`, `3days`, `never` |
| `name` | Name informs the Trello service about the name of your application. This will be displayed to your users during authorization. |

You may configure your server to include this information when creating the server.

```php
// Create a server instance.
$server = new \League\OAuth1\Client\Server\Trello([
    'identifier'              => 'your-identifier',
    'secret'                  => 'your-secret',
    'callbackUri'             => 'http://your-callback-uri/',
    'scope'                   => 'read',
    'expiration'              => '1day',
    'name'                    => 'Trello App'
]);
```

You may also provide this information when creating your authorization url.

```php
// Create a server instance.
$server = new \League\OAuth1\Client\Server\Trello([
    'identifier'              => 'your-identifier',
    'secret'                  => 'your-secret',
    'callbackUri'             => 'http://your-callback-uri/',
]);

$temporaryCredentials = $server->getTemporaryCredentials();

$options = [
    'scope'                   => 'read',
    'expiration'              => '1day',
    'name'                    => 'Trello App'
];

$authorizationUrl = $server->getAuthorizationUrl($temporaryCredentials, $options);
```

Configuration provided when creating your authorization url with take precedence over default configuration.

### Sending one-off requests

You may use the server to create authenticated requests for one-off or trivial needs. If your needs are more robust, [trello-php](https://github.com/stevenmaguire/trello-php) is recommended.

```php
$server = new \League\OAuth1\Client\Server\Trello([
    'identifier'              => 'your-identifier',
    'secret'                  => 'your-secret',
    'callbackUri'             => 'http://your-callback-uri/',
]);

$token = 'your-resource-owner-token';
$secret = 'your-resource-owner-secret';

$tokenCredentials = new \League\OAuth1\Client\Credentials\TokenCredentials($token, $secret);

$request = $server->getAuthenticatedRequest(
    'get',
    'https://api.trello.com/1/members/me/boards',
    $tokenCredentials
);

$client = new \GuzzleHttp\Client();

$response = $client->send($request);

```

### Running the included example

This project contains some example code within the `example` directory at the root of this project.

First, open the `example/index.php` file and update the server configuration with your Trello App Identifier and Secret.

Then run the code in your browser. Using the built-in server provided by PHP may be the fastest options. From the command line, and at the root of the project running the following command.

```bash
php -S localhost:9000 -t example
```

The built-in web server should begin running and when you browse to [`http://localhost:9000`](http://localhost:9000) in your favorite browser, you can begin testing your configuration.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

``` bash
$ ./vendor/bin/phpcs src --standard=psr2 -sp
```

## Contributing

Please see [CONTRIBUTING](https://github.com/thephpleague/oauth1-trello/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Steven Maguire](https://github.com/stevenmaguire)
- [All Contributors](https://github.com/thephpleague/oauth1-trello/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/thephpleague/oauth1-trello/blob/master/LICENSE) for more information.
