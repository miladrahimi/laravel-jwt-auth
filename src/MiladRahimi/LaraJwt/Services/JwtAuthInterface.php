<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 19:58
 */

namespace MiladRahimi\LaraJwt\Services;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;

interface JwtAuthInterface
{
    /**
     * Generate JWT from authenticable model and custom claims
     *
     * @param Authenticatable $user
     * @param array $customClaims
     * @return string
     */
    public function generateToken(Authenticatable $user, array $customClaims = []): string;

    /**
     * Retrieve user from given jwt via appropriate given user provider
     *
     * @param string $jwt
     * @param UserProvider $provider
     * @return Authenticatable|null
     */
    public function retrieveUser(string $jwt, UserProvider $provider = null);

    /**
     * Retrieve claims from given jwt
     * (This method does not support filters and advanced validations)
     *
     * @param string $jwt
     * @return array
     */
    public function retrieveClaims(string $jwt): array;

    /**
     * Is given JWT valid?
     *
     * @param string $jwt
     * @return bool
     */
    public function isJwtValid(string $jwt): bool;

    /**
     * Register new filter
     *
     * @param Closure $hook
     */
    public function registerFilter(Closure $hook);

    /**
     * Clear JWT cache
     *
     * @param Authenticatable|int $user
     */
    public function clearCache($user);

    /**
     * Invalidate jwt by its jti
     *
     * @param string $jti
     */
    public function invalidate(string $jti);
}