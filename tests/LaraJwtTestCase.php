<?php

use Faker\Factory as FakerFactory;
use Illuminate\Support\Str;
use MiladRahimi\LaraJwt\Providers\ServiceProvider;

/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/20/17
 * Time: 12:55
 */
class LaraJwtTestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @var \Faker\Generator $faker
     */
    protected $faker;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $_ENV['APP_ENV'] = 'testing';

        $this->faker = FakerFactory::create();
    }

    /**
     * @inheritdoc
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    /**
     * @inheritdoc
     */
    protected function getEnvironmentSetUp($app)
    {
        // Config
        $app['config']->set('jwt.key', $this->key());
        $app['config']->set('jwt.ttl', 60 * 60 * 24 * 30);
        $app['config']->set('jwt.issuer', 'The Issuer');
        $app['config']->set('jwt.audience', 'The Audience');
    }

    /**
     * Generate JWT Key (Singleton)
     *
     * @return string
     */
    protected function key(): string
    {
        return $_ENV['JWT_KEY'] ?? $_ENV['JWT_KEY'] = Str::random(32);
    }
}