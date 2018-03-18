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
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Lcobucci\JWT\ValidationData;
use MiladRahimi\LaraJwt\Exceptions\LaraJwtConfiguringException;
use Illuminate\Config\Repository as Config;

class JwtAuth implements JwtAuthInterface
{
    /**
     * @var Closure[]
     */
    private $filters = [];

    /**
     * @var string
     */
    private $key;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var string
     */
    private $issuer;

    /**
     * @var string
     */
    private $audience;

    /**
     * @var bool
     */
    private $isModelSafe;

    /**
     * @var CacheManager
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var JwtServiceInterface
     */
    private $service;

    /**
     * JwtAuth constructor.
     *
     * @throws LaraJwtConfiguringException
     */
    public function __construct()
    {
        $this->cache = app('cache');
        $this->config = app('config');
        $this->service = app(JwtServiceInterface::class);
        $this->configure();
    }

    /**
     * Check if configuration file is set up or not
     *
     * @throws LaraJwtConfiguringException
     */
    private function configure()
    {
        if (empty($this->config->get('jwt')) || empty($this->config->get('jwt.key'))) {
            throw new LaraJwtConfiguringException('LaraJwt config not found.');
        }

        $this->key = $this->config->get('jwt.key');
        $this->ttl = $this->config->get('jwt.ttl', 60 * 60 * 24 * 30);
        $this->issuer = $this->config->get('jwt.issuer', 'Issuer');
        $this->audience = $this->config->get('jwt.audience', 'Audience');
        $this->isModelSafe = $this->config->get('jwt.model_safe', false);
    }

    /**
     * @inheritdoc
     */
    public function generateToken(Authenticatable $user, array $customClaims = []): string
    {
        $tokenClaims = [];
        $tokenClaims['sub'] = $user->getAuthIdentifier();;
        $tokenClaims['iss'] = $this->issuer;
        $tokenClaims['aud'] = $this->audience;
        $tokenClaims['exp'] = time() + $this->ttl;
        $tokenClaims['iat'] = time();
        $tokenClaims['nbf'] = time();
        $tokenClaims['jti'] = uniqid();

        foreach ($customClaims as $name => $value) {
            $tokenClaims[$name] = $value;
        }

        if ($this->isModelSafe) {
            $tokenClaims['model'] = get_class($user);
        }

        return $this->service->generate($tokenClaims, $this->key);
    }

    /**
     * @inheritdoc
     */
    public function retrieveUser(string $jwt, UserProvider $provider = null)
    {
        $claims = $this->retrieveClaims($jwt);

        if (empty($claims)) {
            return null;
        }

        if (is_null($provider)) {
            $provider = app(EloquentUserProvider::class);
        }

        $key = $this->getUserCacheKey($claims['sub']);
        $ttl = $this->ttl / 60;

        return $this->cache->remember($key, $ttl, function () use ($claims, $provider) {
            $user = $provider->retrieveById($claims['sub']);

            if ($user == null) {
                return null;
            }

            if ($this->isModelSafe) {
                if (empty($claims['model']) || get_class($user) != $claims['model']) {
                    return null;
                }
            }

            return $this->applyFilters($user);
        });
    }

    /**
     * @inheritdoc
     */
    public function retrieveClaims(string $jwt): array
    {
        if ($this->isJwtValid($jwt) == false) {
            return [];
        }

        return $this->service->parse($jwt, $this->key);
    }

    /**
     * @inheritdoc
     */
    public function isJwtValid(string $jwt): bool
    {
        try {
            $validator = new ValidationData();
            $validator->setIssuer($this->issuer);
            $validator->setAudience($this->audience);

            $claims = $this->service->parse($jwt, $this->key, $validator);

            if (isset($claims['sub'], $claims['jti'], $claims['exp']) == false) {
                return false;
            }

            if ($this->isTokenInvalidated($claims['jti'])) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function registerFilter(Closure $hook)
    {
        $this->filters[] = $hook;
    }

    /**
     * @inheritdoc
     */
    private function applyFilters(Authenticatable $user)
    {
        if (empty($this->filters)) {
            return $user;
        }

        foreach ($this->filters as $hook) {
            $user = $hook($user);

            if ($user == null) {
                return null;
            }
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function clearCache($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        $this->cache->forget($this->getUserCacheKey($user));
    }

    /**
     * @param $user
     * @return string
     */
    private function getUserCacheKey($user)
    {
        if ($user instanceof Authenticatable) {
            $user = $user->getAuthIdentifier();
        }

        return 'jwt:users:' . $user;
    }

    /**
     * @inheritdoc
     */
    public function invalidate(string $jti)
    {
        $this->cache->put($this->getTokenInvalidationCacheKey($jti), time(), $this->ttl / 60);
    }

    /**
     * @param string $jti
     * @return string
     */
    private function getTokenInvalidationCacheKey(string $jti)
    {
        return "jwt:invalidated:$jti";
    }

    /**
     * Check if token is invalidated
     *
     * @param string $jti
     * @return bool
     */
    private function isTokenInvalidated(string $jti): bool
    {
        return $this->cache->get($this->getTokenInvalidationCacheKey($jti)) ?: false;
    }
}