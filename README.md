# LaraJwt
Laravel JWT guard and authentication tools

## Documentation

### Overview

LaraJwt is a Laravel package for generating JWT (JSON Web-based Token) from users and providing JWT guard for Laravel 
applications.

### Installation

Add the package via Composer:

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

To configure the package open `jwt.php` file in your laravel config directory. This files consists of following items:

* `key`: The secret key to sign the token, it uses your project key if you leave it empty.
* `ttl`: Time that token will be valid, token will be expired after this time (in seconds)
* `issuer`: Issuer claim
* `audience`: Audience claim
* `model_safe`: Set it true if you have different authentication for different models with LaraJwt, it ensures that
    token belongs to related model defined in guard.

### Generate JWT from Users

Use the method below to generate JWT from users or any other authenticable entities (models):

```
$jwt = JwtAuth::generateToken($user);
```

For example you may generate JWT from users in the sign-in process like this:

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

If you want to store more information like role in the token, you can pass them to the method this way:

```
$customClaims = ['role' => 'admin', 'foo' => 'bar'];

$jwt = JwtAuth::generateToken($user, $customClaims);
```

### Guards

Add as many as guard you need in your `config/auth.php` with `jwt` driver like this example:

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

### Authenticated Routes

After configuring guards in `config/auth.php` you can protect routes by the defined guards.

In our example we can protect route like this:

```
Route::group(['middleware' => 'auth:api'], function () {
    // Routes...
});
```

* Your clients must send header `Authorization: Bearer <jwt>` in their requests.

### Authenticated User

To retrieve current user and his info in your application (controllers for example) you can do it this way:

```
// To get current user
$user = Auth::guard('api')->user();
$user = Auth::guard('api')->getUser();

// To get current user id
$user = Auth::guard('api')->id();

// Is current user guest
$user = Auth::guard('api')->guest();

// To get current token
$jwt = Auth::guard('api')->getToken();

// To get current token claims
$claims = Auth::guard('api')->getClaims();

// To get a sepcific claim from current token
$role = Auth::guard('api')->getClaim('role');

// Logout current user (JWT will be cached in blacklist and NOT valid in next requests).
Auth::guard('api')->logout();

// Logout current user (but it will be VALID next reuqests).
// It clears caches so user will be fetched and filters will be executed again in next request.
Auth::guard('api')->logout(false);
```

Since LaraJwt caches user fetching it can authenticate users without touching database.

### Retrieve User Manually

You may need to retrieve user from generated JWTs manually, no worry! just do it this way:

```
$user = JwtAuth::retrieveUser($jwt);

```

It uses default user provider to fetch the user,
if you are using different provider you can pass it to the method as the second parameter like this:

```
$admin = JwtAuth::retrieveUser($jwt, 'admin');
```

### Retrieve JWT Claims Manually

You my even go further and need to retrieve JWT claims manually, it has considered too.

```
$claims = JwtAuth::retrieveClaims($jwt);
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

### Cache

LaraJwt caches retrieving users process , so after first successful authentication it remembers jwt as long as ttl 
which is set in config, you may clear this cache to force LaraJwt to re-run filters or re-fetch user model from 
database, to do so you can use the this method:

```
JwtAuth::clearCache($user);
```

You can pass user model (Authenticable) or its primary key to the `clearCache` method.

### Filters

Filters are runnable closures which will be called after parsing token and fetching user model.

For example if you have considered boolean property like `is_active` for users,
you probably want to check its value after authentication and raise some exception if it is false or change LaraJwt 
normal process the way it be seemed authentication is failed.

You can register filters as many as you need, LaraJwt runs them one by one after authentication.

AuthServiceProvider seems a good place to register hooks.

```
class AuthServiceProvider extends ServiceProvider
{
    // ...

    public function boot()
    {
        // ...
        
        $jwtAuth = $this->app->make(JwtAuthInterface::class);
        
        // Check if user is active or not
        $jwtAuth->registerFilter(function (User $user) {
            if ($user->is_active == true) {
                return $user;
            } else {
                return null;
            }
        });
    }
}
```

The `registerFilter` takes a closure with one argument to get authenticated user and it should return the user if there
is no problem, it can return null if you want make the authentication failed.

### Logout and JWT Invalidation

As mentioned with example above you can logout user with following method:

```
Auth::guard('api')->logout();
```

It takes one boolean parameter that is true in default and put the jwt in cached blacklist so the token won't be valid
in next requests, but you can pass false to make it only logout current user and clear cache.

You can also invalidate tokens with `JwtAuth` facade and `jti` claim this way:

```
JwtAuth::invalidate($jti);
```

### Exceptions

```
Exception Class: LaraJwtConfiguringException
Exception Message: LaraJwt config not found.
```

This exception would be thrown if you had not published the package config (mentioned in Installation section).

### JWT vs Stored Tokens

You may consider simple database-stored tokens as the alternative for JWT for authenticating,
So we have provided some differences and comparison for you.

#### Cons

* More HTTP overhead, generated tokens are long.
* Force logout is more complex and tricky (LaraJwt handles it for you).

#### Pros

* No need to database column for storing generated tokens.
* No database touch if you only need user id.
* Less database touch if you cache user fetching (LaraJwt does it for you).

### Contribute

Any contribution will be appreciated :D

## License
This package is released under the [MIT License](http://opensource.org/licenses/mit-license.php).
