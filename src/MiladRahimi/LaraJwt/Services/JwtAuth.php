<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 19:59
 */

namespace MiladRahimi\LaraJwt\Services;

use Closure;
use Exception;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Auth\Authenticatable;
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
        try {
            if (empty(app('config')->get('jwt'))) {
                throw new LaraJwtConfiguringException('LaraJwt config not found.');
            }
        } catch (EntryNotFoundException $e) {
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

        if (is_null($provider)) {
            $provider = app(EloquentUserProvider::class);
        }

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
    public function isJwtValid($jwt): bool
    {
        if (empty($jwt)) {
            return false;
        }

        try {
            $claims = $this->retrieveClaimsFrom($jwt);

            return isset($claims['sub'], $claims['jti'], $claims['exp']);
        } catch (Exception $e) {
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

        app('cache')->forget($this->getUserCacheKey($user));
    }

    /**
     * @inheritdoc
     */
    public function getUserCacheKey($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        return 'jwt:users:' . $user;
    }

    /**
     * @inheritdoc
     */
    public function logout($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        $ttl = app('config')->get('jwt.ttl') / 60;

        app('cache')->put($this->getUserLogoutCacheKey($user), time(), $ttl);
    }

    /**
     * @inheritdoc
     */
    public function getUserLogoutCacheKey($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        return "jwt:users:$user:logout";
    }
}