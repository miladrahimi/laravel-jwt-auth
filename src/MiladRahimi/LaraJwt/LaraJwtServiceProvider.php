<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/19/17
 * Time: 13:48
 */

namespace MiladRahimi\LaraJwt;

use Illuminate\Support\ServiceProvider;

class LaraJwtServiceProvider extends ServiceProvider
{
    /**
     * Register
     *
     * @return void
     */
    public function register()
    {
        //
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
            $guard = new LaraJwtGuard(
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        // Install config on vendor:publish
        $this->publishes([
            __DIR__ . '/../../../config/jwt.php' => config_path('jwt.php'),
        ]);
    }
}