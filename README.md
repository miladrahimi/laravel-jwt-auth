# LaraJwt
Laravel JWT guard and authentication tools

## Documentation

### Overview

LaraJwt is a Laravel package for
generating JWT (JSON Web-based Token) from users
and providing JWT guard for Laravel applications.

### Installation

Run the following command in your Laravel root directory:

```
composer require miladrahimi/larajwt:2.*
```

Then run the following command to generate `jwt.php` (the package config) in your Laravel config directory:

```
php artisan vendor:publish --tag=larajwt-config
```

#### Notes on Installation

* Edit `config/jwt.php` based on your requirements and environment.

* The package service provider will be automatically discovered by Laravel package discovery.

* The `JwtAuth` alias for `MiladRahimi\LaraJwt\Facades\JwtAuth` will be automatically registered.

### Generate JWT from Users

Use the method below to generate JWT from users or any other authenticable entities (models):

```
$jwt = JwtAuth::generateTokenFrom($user);
```

For example you may generate JWT from users in the sign in process like this:

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

If you want to store more information like role in the token, you can pass them to the method this way:

```
$claims = ['role' => 'admin', 'foo' => 'bar'];

$jwt = JwtAuth::generateTokenFrom($user, $claims);
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

// To get current user id
$user = Auth::guard('api')->id();

// To get current token
$jwt = Auth::guard('api')->getToken();

// To get current token claims
$claims = Auth::guard('api')->getClaims();

// To get a sepcific claim from current token
$role = Auth::guard('api')->getClaim('role');
```

Since LaraJwt caches user fetching it can authenticate users without touching database.

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

### Cache

LaraJwt caches JWTs in default,
so after first authentication it remembers token as long as ttl which is set in config.

If you need to clear cache for a specific user you can use following method:

```
JwtAuth::clearCache($user);
```

You can pass user model (Authenticable) or its id to the `clearCache` method.

### Post-hooks

Post-hooks are closures which will be called after authentication.

For example if you have considered boolean property like `is_active` for users,
you probably want to check its value after authentication and raise some exception if it is false.

You can register hooks as many as you need, LaraJwt runs them after authentication.

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
        $jwtAuth->registerPostHook(function (User $user) {
            if ($user->is_active == false) {
                throw new UserIsNotActiveException();
            }
        });
    }
}
```

The `registerPostHook` takes a closure with one argument to get authenticated user.

You may put following snippet to the `render` method in `Laravel/app/Exceptions/Handler.php`:

```
public function render($request, Exception $exception)
{
    if ($exception instanceof UserIsNotActiveException) {
        return response()->json(['error' => 'You are not active...'], 403);
    }
    
    // ...
}
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
