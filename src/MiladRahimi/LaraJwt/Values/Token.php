<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/18/17
 * Time: 15:49
 */

namespace MiladRahimi\LaraJwt\Values;

class Token
{
    public $iss;
    public $sub;
    public $aud;
    public $exp;
    public $nbf;
    public $iat;
    public $jti;
}