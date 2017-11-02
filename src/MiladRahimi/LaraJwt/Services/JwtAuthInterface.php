<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 19:58
 */

namespace MiladRahimi\LaraJwt\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;

interface JwtAuthInterface
{
    /**
     * Generate JWT from user model or id
     *
     * @param Authenticatable|int $user
     * @param array $claims
     * @return string
     */
    public function generateToken($user, array $claims = []): string;

    /**
     * Fetch user from token
     *
     * @param string $jwt
     * @return Authenticatable
     * @throws InvalidJwtException
     */
    public function fetchUser(string $jwt): Authenticatable;
}