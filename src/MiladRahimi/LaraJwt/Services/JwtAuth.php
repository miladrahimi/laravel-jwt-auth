<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 19:59
 */

namespace MiladRahimi\LaraJwt\Services;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;
use MiladRahimi\LaraJwt\Exceptions\LaraJwtConfiguringException;

class JwtAuth implements JwtAuthInterface
{
    /** @var Closure[] $postHooks */
    private $postHooks = [];

    /**
     * JwtAuth constructor.
     *
     * @throws LaraJwtConfiguringException
     */
    public function __construct()
    {
        if (empty(app('config')->get('jwt'))) {
            throw new LaraJwtConfiguringException('LaraJwt config not found.');
        }
    }

    /**
     * @inheritdoc
     */
    public function generateTokenFrom($user, array $claims = []): string
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        $tokenClaims = [];
        $tokenClaims['sub'] = $user;
        $tokenClaims['iss'] = app('config')->get('jwt.issuer');
        $tokenClaims['aud'] = app('config')->get('jwt.audience');
        $tokenClaims['exp'] = time() + intval(app('config')->get('jwt.ttl'));
        $tokenClaims['iat'] = time();
        $tokenClaims['nbf'] = time();
        $tokenClaims['jti'] = uniqid('jwt');

        foreach ($claims as $name => $value) {
            $tokenClaims[$name] = $value;
        }

        $jwtService = app(JwtServiceInterface::class);
        return $jwtService->generate($tokenClaims, app('config')->get('jwt.key'));
    }

    /**
     * @inheritdoc
     */
    public function retrieveUserFrom(string $jwt, $provider = null): Authenticatable
    {
        $claims = $this->retrieveClaimsFrom($jwt);

        /** @var UserProvider $provider */
        $provider = app('auth')->getProvider($provider);

        return $provider->retrieveById(($claims['sub'] ?? null));
    }

    /**
     * @inheritdoc
     */
    public function retrieveClaimsFrom(string $jwt): array
    {
        /** @var JwtServiceInterface $jwtService */
        $jwtService = app(JwtServiceInterface::class);

        return $jwtService->parse($jwt, app('config')->get('jwt.key'));
    }

    /**
     * @inheritdoc
     */
    public function isJwtValid(string $jwt): bool
    {
        try {
            $claims = $this->retrieveClaimsFrom($jwt);

            return isset($claims['sub']);
        } catch (InvalidJwtException $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function registerPostHook(Closure $hook)
    {
        $this->postHooks[] = $hook;
    }

    /**
     * @inheritdoc
     */
    public function runPostHooks($user)
    {
        foreach ($this->postHooks as $hook) {
            $hook($user);
        }
    }

    /**
     * @inheritdoc
     */
    public function clearCache($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        $key = 'jwt:users:' . $user;

        app('cache')->forget($key);
    }

    /**
     * @inheritdoc
     */
    public function invalidate($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        /** @var \Illuminate\Cache\CacheManager $cache */
        $cache = app('cache');

        $ttl = app('config')->get('jwt.ttl') / 60;

        $cache->put("jwt:users:$user:logout", time(), $ttl);
    }
}