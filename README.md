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

Now run the following command to copy `jwt.php` to your Laravel config directory:

```
php artisan vendor:publish --tag=larajwt-config
```

#### Notes on Installation

* The package service provider will be automatically discovered by Laravel package discovery.

* The `JwtAuth` alias for `MiladRahimi\LaraJwt\Facades\JwtAuth` will be automatically registered.

### Configuration

Add JWT guard in your `config/auth.php` like the example.

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

You may edit `config/jwt.php` based on your your requirements.

### Generate JWT from Users

Use the following method to generate jwt from user model:

```
$jwt = JwtAuth::generateToken($user);
```

You may pass any authenticable entity to this method.

For example you may generate a jwt from user in sign in process like this:

```
$credential = [
    'email' => $request->input('email'),
    'password' => $request->input('password'),
];
    
if(Auth::guard('api')->attempt($credential)) {
    $user = Auth::guard('api')->user();
    $jwt = JwtAuth::generateToken($user);
    
    // Return successfull sign in response with the generated jwt.
} else {
    // Return response for failed attempt...
}
```

### User Validation

Your clients must add `Authorization: Bearer <jwt>` to the header of their requests.

For example if you have considered JWT guard for your APIs (in `config/auth.php`),
you can `auth:api` middleware to your authenticated API routes.
and to retrieve current user in your application you can use following code:

```
$currentUser = Auth::guard('api')->user();
```

### Contribute

Any contribution will be appreciate it :D

## License
This package is released under the [MIT License](http://opensource.org/licenses/mit-license.php).