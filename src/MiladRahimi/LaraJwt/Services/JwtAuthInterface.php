<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 19:58
 */

namespace MiladRahimi\LaraJwt\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\ServiceProvider;
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
    public function generateTokenFrom($user, array $claims = []): string;

    /**
     * Retrieve user from given jwt and by given user provider
     *
     * @param string $jwt
     * @param ServiceProvider|string $provider
     * @return Authenticatable
     */
    public function retrieveUserFrom(string $jwt, $provider = null): Authenticatable;

    /**
     * Retrieve claims from given jwt
     *
     * @param string $jwt
     * @return array
     */
    public function retrieveClaimsFrom(string $jwt): array;

    /**
     * Is given JWT valid?
     *
     * @param string $jwt
     * @return bool
     */
    public function isJwtValid(string $jwt): bool;
}