<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/19/17
 * Time: 13:48
 */

namespace MiladRahimi\LaraJwt\Providers;

use Illuminate\Support\ServiceProvider as Provider;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use MiladRahimi\LaraJwt\Guards\Jwt as JwtGuard;
use MiladRahimi\LaraJwt\Services\JwtAuth;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;
use MiladRahimi\LaraJwt\Services\JwtService;
use MiladRahimi\LaraJwt\Services\JwtServiceInterface;

class ServiceProvider extends Provider
{
    /**
     * Register
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(JwtServiceInterface::class, JwtService::class);
        $this->app->singleton(JwtAuthInterface::class, JwtAuth::class);

        $this->app->bind('larajwt.signer', Sha512::class);
    }

    /**
     * Boot
     *
     * @return void
     */
    public function boot()
    {
        // Extend laravel auth to inject jwt guard
        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {

            $guard = new JwtGuard(
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        // Install config on vendor:publish
        $this->publishes([
            __DIR__ . '/../../../../config/jwt.php' => config_path('jwt.php')
        ], 'larajwt-config');
    }
}