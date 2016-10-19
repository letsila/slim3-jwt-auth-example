# Slim3 jwt sample

This is an example of implementation of JWT authentication using Slim3. This code can be used in pair with
the [ionic-jwt-sample] (https://github.com/letsila/ionic2-jwt-sample).

## Dependencies
The only dependency used is [firebase/php-jwt] (https://github.com/firebase/php-jwt) for creating and decoding the JSON
web token.


## Storage
For simplicity sake, users informations are stored in a JSON file named user.json located at the root of the project.
An unique table named tokens have been used for storing the tokens.

## Routes
All the interesting stuff are placed inside the index.php under the public folder where we have defined two routes in.

1. The authentication route which allows us to get the credentials and the token sent from the client for validation.
```php
$app->post('/authenticate', function (Request $request, Response $response) {
    ...
})
```

2. A route which handle a get request for requiring secured ressource.
```php
$app->get('/secured-data', function (Request $request, Response $response) {
    ...
})
```



