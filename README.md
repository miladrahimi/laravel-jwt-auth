# LaraJwt
Laravel JWT guard and authentication tools

## Documentation

### Overview

LaraJwt is a Laravel package for
generating JWT (JSON Web-based Token) from users
and providing JWT guard for Laravel applications.

### Installation

Run the following command in your Laravel project root directory:

```
composer require miladrahimi/larajwt:2.*
```

Then run the following command to generate `jwt.php` (the package config) in your Laravel config directory:

```
php artisan vendor:publish --tag=larajwt-config
```

#### Notes on Installation

* The package service provider will be automatically discovered by Laravel package discovery.

* The `JwtAuth` alias for `MiladRahimi\LaraJwt\Facades\JwtAuth` will be automatically registered.

### Configuration

Add as many as JWT guard you need in your `config/auth.php` like the example.

```
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

You also may want tp edit `config/jwt.php` based on your your requirements, feel free to do it!

### Generate JWT from Users

Use the method below to generate JWT from any authenticable entity (model):

```
$jwt = JwtAuth::generateToken($user);
```

For example you may generate a JWT from user in sign in process like this:

```
$credential = [
    'email' => $request->input('email'),
    'password' => $request->input('password'),
];
    
if(Auth::guard('api')->attempt($credential)) {
    $user = Auth::guard('api')->user();
    $jwt = JwtAuth::generateTokenFrom($user);
    
    // Return successfull sign in response with the generated jwt.
} else {
    // Return response for failed attempt...
}
```

### Authenticated Routes

After configuring guards in `config/auth.php` you can protect routes by the defined guards.

For the auth configuration demonstrated above you can protect routes this way:

```
Route::group(['middleware' => 'auth:api'], function () {
    // Routes...
});
```

### Authenticated User

Your clients must send header `Authorization: Bearer <jwt>` in their requests.

To retrieve current user in your application (controllers for example) you can do it this way:

```
$currentUser = Auth::guard('api')->user();
```

### Retrieve User Manually

You may need to retrieve user from generated JWTs manually, no worry! just do it this way:

```
$user = JwtAuth::retrieveUserFrom($jwt);

```

It uses default user provider to fetch the user,
if you are using different provider you can pass it to the method as the second parameter like this:

```
$admin = JwtAuth::retrieveUserFrom($jwt, 'admin');
```

### Retrieve JWT Claims Manually

You my even go further and need to retrieve JWT claims manually, it has considered too.

```
$claims = JwtAuth::retrieveClaimsFrom($jwt);
```

The mentioned method returns associative array of claims with following structure:

```
[
    'sub' => '666',
    'iss' => 'Your app name',
    'aud' => 'Your app audience',
    // ...
]
```

### Exceptions

```
Exception Class: LaraJwtConfiguringException
Exception Message: LaraJwt config not found.
```

This exception would be thrown if you had not published the package config (mentioned in Installation section).

### Contribute

Any contribution will be appreciate it :D

## License
This package is released under the [MIT License](http://opensource.org/licenses/mit-license.php).