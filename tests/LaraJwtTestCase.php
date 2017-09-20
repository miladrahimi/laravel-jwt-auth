<?php

use Illuminate\Support\Str;

/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/20/17
 * Time: 12:55
 */
class LaraJwtTestCase extends \PHPUnit\Framework\TestCase
{
    protected function mockConfig()
    {

        function config($key)
        {
            switch ($key) {
                case 'jwt.key':
                    return $this->key();
                case 'jwt.issuer':
                    return 'MiladRahimi';
                case 'jwt.audience':
                    return 'Audiences';
                case 'jwt.ttl':
                    return 60 * 60 * 24;
                default:
                    return Str::random(32);
            }
        }
    }

    protected function key(): string
    {
        static $key = null;

        return $key ?: $key = Str::random(32);
    }
}