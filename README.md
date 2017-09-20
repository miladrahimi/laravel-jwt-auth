# LaraJwt
JWT authentication tools and guard for Laravel projects

## Documentation

### Overview

LaraJwt is a simple laravel package to generate jwt and providing jwt guard for authentication.

### Installation

Run following command in your laravel root directory:

```
composer require miladrahimi/larajwt:1.*
```

After installation add LaraJwt service provider to the providers in `config/app.php`:

```
MiladRahimi\LaraJwt\LaraJwtServiceProvider::class
```

Then run following command to add `jwt.php` (LaraJwt config) to your project configs:

```
php artisan vendor:publish
```

### Configuration

Add JWT guard in your `config/auth.php`:

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

Open `config/jwt.php` and edit it based on your requirements.

### Generate JWT

Use following method to generate jwt from user model:

```
use MiladRahimi\LaraJwt\JwtAuth;

$jwt = JwtAuth::generateToken($user);
```

### User Authentication

You may authenticate users in the sign in process like this:

```
$credential = [
    'email' => $request->input('email'),
    'password' => $request->input('password'),
];
    
if(Auth::guard('api')->attempt($credential)) {
    $user = Auth::guard('api')->user();
    $jwt = JwtAuth::generateToken($user);
    
    // Return successfull sign in response and the generate jwt.
} else {
    // Return failed sign in attempt...
}
```

### User Validation

You clients must add `Authorization: Bearer <jwt>` to the header of their requests.

Add `auth:api` (based on your `config/auth.php`) middleware to your authenticated routes.

You can retrieve current user in your application this way:

```
$currentUser = Auth::guard('api')->user();
```

or if your are in a controller:


```
$currentUser = $this->guard()->user();
```

### Contribute

Any contribution will be appreciate it :D

## License
This package is released under the [MIT License](http://opensource.org/licenses/mit-license.php).