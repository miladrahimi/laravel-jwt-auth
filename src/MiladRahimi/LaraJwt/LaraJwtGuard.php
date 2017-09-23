<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/19/17
 * Time: 13:51
 */

namespace MiladRahimi\LaraJwt;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;
use MiladRahimi\LaraJwt\Values\Token;

class LaraJwtGuard implements Guard
{
    /** @var UserProvider $provider */
    protected $provider;

    /** @var Request $request */
    protected $request;

    /** @var Token $token */
    protected $token;

    /** @var Authenticatable $user */
    protected $user;

    /**
     * Create a new authentication guard.
     *
     * @param UserProvider $provider
     * @param Request $request
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;

        $this->retrieveToken();
    }

    /**
     * Retrieve token from header
     */
    private function retrieveToken()
    {
        if ($authorization = $this->request->header('Authorization')) {
            if (Str::startsWith($authorization, 'Bearer ')) {
                $jwt = substr($authorization, strlen('Bearer '));
            } else {
                $jwt = $authorization;
            }

            try {
                $this->token = JwtService::parse($jwt, config('jwt.key'));
            } catch (InvalidJwtException $e) {
                $this->token = null;
            }
        }
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return is_null($this->user) && (is_null($this->token) || empty($this->token->sub));
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if ($this->user) {
            return $this->user->getAuthIdentifier();
        }

        if ($this->check()) {
            return $this->token->sub;
        }

        return null;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return $this->user || ($this->token && $this->token->sub);
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
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
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
        if ($this->user) {
            return $this->user;
        }

        if ($this->check()) {
            return $this->user = $this->provider->retrieveById($this->token->sub);
        }

        return null;
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
}