<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/19/17
 * Time: 15:29
 */

namespace MiladRahimi\LaraJwt;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use MiladRahimi\LaraJwt\Values\Token;

class JwtAuth
{
    /**
     * Generate JWT from user model or id
     *
     * @param Authenticatable|int $user
     * @return string
     */
    public static function generateToken($user): string
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        $token = new Token();
        $token->sub = $user;
        $token->iss = self::config('issuer');
        $token->aud = self::config('audience');
        $token->exp = time() + intval(config('jwt.ttl'));
        $token->iat = time();
        $token->nbf = time();
        $token->jti = base64_encode(random_bytes(128));

        return JwtService::generate($token, self::config('key'));
    }

    /**
     * Fetch user from token
     *
     * @param string $jwt
     * @return Authenticatable
     */
    public static function fetchUser(string $jwt): Authenticatable
    {
        $token = JwtService::parse($jwt, self::config('key'));

        /** @var UserProvider $provider */
        $provider = app(UserProvider::class);

        return $provider->retrieveById($token->sub);
    }

    /**
     * Get config
     *
     * @param string $key
     * @return int|string|null
     */
    public static function config(string $key)
    {
        return config('jwt.' . $key);
    }
}