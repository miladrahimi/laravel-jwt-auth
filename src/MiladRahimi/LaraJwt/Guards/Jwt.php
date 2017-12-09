<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/19/17
 * Time: 13:51
 */

namespace MiladRahimi\LaraJwt\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;

class Jwt implements Guard
{
    use GuardHelpers;

    /** @var UserProvider $provider */
    protected $provider;

    /** @var Request $request */
    protected $request;

    /** @var Authenticatable $user */
    protected $user;

    /** @var string $token */
    protected $token;

    /** @var JwtAuthInterface $jwtAuth */
    protected $jwtAuth;

    /** @var array $claims */
    protected $claims = [];

    /**
     * Create a new authentication guard.
     *
     * @param UserProvider $provider
     * @param Request $request
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function __construct(UserProvider $provider = null, Request $request = null)
    {
        $this->provider = $provider;
        $this->request = $request;

        $this->jwtAuth = app(JwtAuthInterface::class);

        $this->retrieveUserInfo();
    }

    /**
     * Retrieve user from jwt token in the request header
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    private function retrieveUserInfo()
    {
        $this->token = $this->getToken();

        if (is_null($this->token) || $this->jwtAuth->isJwtValid($this->token) == false) {
            return null;
        }

        $this->claims = $this->jwtAuth->retrieveClaimsFrom($this->token);

        $logoutTime = app('cache')->get($this->jwtAuth->getLogoutCacheKey($this->claims['sub']));

        if ($logoutTime && $logoutTime > $this->claims['exp']) {
            return null;
        }

        $key = $this->jwtAuth->getUserCacheKey($this->claims['sub']);

        $ttl = app('config')->get('jwt.ttl') / 60;

        $this->user = app('cache')->remember($key, $ttl, function () {
            $user = $this->jwtAuth->retrieveUserFrom($this->token);

            $this->jwtAuth->runPostHooks($user);

            return $user;
        });
    }

    /**
     * Get current user JWT
     *
     * @return null|string
     */
    public function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $authorization = $this->request->header('Authorization');

        if ($authorization && starts_with($authorization, 'Bearer ')) {
            return $this->token = substr($authorization, strlen('Bearer '));
        }

        return null;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return is_null($this->user);
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        return $this->user ? $this->user->getAuthIdentifier() : null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return $this->attempt($credentials, false);
    }

    /**
     * Attempt to authenticate the user using the given credentials and return the token.
     *
     * @param array $credentials
     * @param bool $login
     *
     * @return mixed
     */
    public function attempt(array $credentials = [], $login = true)
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            return $login ? $this->login($user) : true;
        }

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param mixed $user
     * @param array $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return $user && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Create a token for a user.
     *
     * @param Authenticatable $user
     *
     * @return string
     */
    public function login(Authenticatable $user)
    {
        $this->setUser($user);

        return $this->user();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->user);
    }

    /**
     * Set the current request instance.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the user provider used by the guard.
     *
     * @return UserProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
     *
     * @param UserProvider $provider
     *
     * @return $this
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Return the currently cached user.
     *
     * @return Authenticatable|null
     */
    public function getUser()
    {
        return $this->user();
    }

    /**
     * Set the current user.
     *
     * @param  Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * Logout current user (Invalidate his/her JWT)
     */
    public function logout()
    {
        if ($this->user) {
            $this->jwtAuth->logout($this->user);
            $this->user = null;
        }
    }

    /**
     * Get stored jwt claim
     *
     * @param string $key
     * @return mixed|null
     */
    public function getClaim(string $key)
    {
        return isset($this->claims[$key]) ? $this->claims[$key] : null;
    }

    /**
     * Get all claims
     *
     * @return array
     */
    public function getClaims(): array
    {
        return $this->claims;
    }
}