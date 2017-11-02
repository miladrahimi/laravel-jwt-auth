<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 20:04
 */

namespace MiladRahimi\LaraJwt\Facades;

use Illuminate\Support\Facades\Facade;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;

class JwtAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return JwtAuthInterface::class;
    }
}