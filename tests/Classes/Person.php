<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 3/18/2018 AD
 * Time: 13:16
 */

namespace MiladRahimi\LaraJwtTests\Classes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Person extends Model implements \Illuminate\Contracts\Auth\Authenticatable
{
    use Authenticatable;
}