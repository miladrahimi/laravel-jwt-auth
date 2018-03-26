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

    /** @var Request $request */
    protected $request;

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
     */
    public function __construct(UserProvider $provider = null, Request $request = null)
    {
        $this->provider = $provider;
        $this->request = $request;

        $this->jwtAuth = app(JwtAuthInterface::class);

        $this->retrieveUser();
    }

    /**
     * Retrieve user from jwt token in the request header
     */
    private function retrieveUser()
    {
        $this->retrieveToken();

        if ($this->getToken()) {
            $this->claims = $this->jwtAuth->retrieveClaims($this->getToken());
            $this->user = $this->jwtAuth->retrieveUser($this->getToken(), $this->getProvider());
        }
    }

    /**
     * Retrieve token from header
     */
    private function retrieveToken()
    {
        $authorization = $this->request->header('Authorization');

        if ($authorization && starts_with($authorization, 'Bearer ')) {
            $this->token = substr($authorization, strlen('Bearer '));
        }
    }

    /**
     * Get current user JWT
     *
     * @return null|string
     */
    public function getToken()
    {
        return $this->token;
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
     * Return the currently cached user.
     *
     * @return Authenticatable|null
     */
    public function getUser()
    {
        return $this->user();
    }

    /**
     * Logout current user (And invalidate his/her JWT)
     *
     * @param bool $invalidateToken
     */
    public function logout(bool $invalidateToken = true)
    {
        if ($this->user()) {
            if ($invalidateToken) {
                $this->jwtAuth->invalidate($this->getClaims()['jti']);
            }

            $this->jwtAuth->clearCache($this->user());
            $this->user = null;
            $this->token = null;
        }
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

    /**
     * Get a specific claim
     *
     * @param string $name
     * @return mixed|null
     */
    public function getClaim(string $name)
    {
        return $this->claims[$name] ?? null;
    }
}