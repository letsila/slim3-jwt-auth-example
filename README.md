# Slim3 jwt sample

This is an example of implementation of JWT authentication on the server side, using [Slim3] (http://www.slimframework.com/). This code can be used in pair with
the [ionic2-jwt-sample] (https://github.com/letsila/ionic2-jwt-sample) a sample code on JWT via an Ionic2 app.

## Dependencies
The only dependency used is [firebase/php-jwt] (https://github.com/firebase/php-jwt) for creating and decoding the JSON
web token.


## Storage
For simplicity sake, users informations are stored in a JSON file named users.json located at the root of the project.
An unique table named tokens have been used for storing the tokens.

## Routes
All the interesting stuff are placed inside the index.php under the public folder where we have defined two routes in.

1. The authentication route which allows us to get the credentials and the token sent from the client for validation.
```php
$app->post('/authenticate', function (Request $request, Response $response) {
    ...
})
```

2. A route which handle a get request for requiring secured ressource to test out our JWT implementation. This route expected
that a token is set on the authorisation header of the request. The token will be validated and if it succeed, we return
the requested resource to the client.
```php
$app->get('/secured-data', function (Request $request, Response $response) {
    ...
})
```

## License
[MIT] (https://opensource.org/licenses/mit-license.php) ©


