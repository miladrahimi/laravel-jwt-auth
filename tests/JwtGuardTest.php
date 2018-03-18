<?php

namespace MiladRahimi\LaraJwtTests;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use MiladRahimi\LaraJwt\Guards\Jwt;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;
use MiladRahimi\LaraJwtTests\Classes\Person;
use Mockery;

/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 12/6/2017 AD
 * Time: 10:59
 */
class JwtGuardTest extends LaraJwtTestCase
{
    /** @var Jwt $guard */
    private $guard;

    /** @var Authenticatable $user */
    private $user;

    /** @var string $token */
    private $token;

    public function setUp()
    {
        parent::setUp();

        $this->user = $this->generateUser();

        $this->guard = $this->createJwtGuard();
    }

    private function createJwtGuard(array $claims = []): Jwt
    {
        $this->mockUserProvider($this->user);

        $this->token = app(JwtAuthInterface::class)->generateToken($this->user, $claims);
        $jwt = 'Bearer ' . $this->token;

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('header')
            ->withArgs(['Authorization'])
            ->andReturn($jwt)
            ->getMock();

        return new Jwt($this->app[EloquentUserProvider::class], $request);
    }

    private function createVirginGuard()
    {
        $this->mockUserProvider($this->user);

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('header')
            ->withArgs(['Authorization'])
            ->andReturn('')
            ->getMock();

        return new Jwt($this->app[EloquentUserProvider::class], $request);
    }

    public function test_get_token_method_it_should_return_the_token()
    {
        $this->assertEquals($this->token, $this->guard->getToken());
    }

    public function test_get_token_method_it_should_return_null_when_there_is_no_user()
    {
        $this->assertNull($this->createVirginGuard()->getToken());
    }

    public function test_login_and_user_and_get_user_methods_it_should_set_user()
    {
        $person = new Person();
        $guard = $this->createVirginGuard();
        $guard->login($person);

        $this->assertSame($guard->getUser(), $person);
        $this->assertSame($guard->user(), $person);
    }

    public function test_user_method_it_should_return_the_user()
    {
        $this->assertTrue($this->guard->user() instanceof Authenticatable);
    }

    public function test_guest_method_is_should_return_false_when_user_is_available()
    {
        $this->assertFalse($this->guard->guest());
    }

    public function test_id_method_it_should_return_the_user_id()
    {
        $this->assertEquals($this->user->getAuthIdentifier(), $this->guard->id());
    }

    public function test_logout_it_should_unset_user_from_guard()
    {
        $guard = $this->createJwtGuard();
        $guard->logout(false);

        $this->assertNull($guard->user());
        $this->assertNull($guard->getUser());
        $this->assertNull($guard->getToken());
    }

    public function test_logout_it_should_unset_user_from_guard_and_invalidate_jwt()
    {
        /** @var JwtAuthInterface $auth */
        $auth = $this->app[JwtAuthInterface::class];

        $guard = $this->createJwtGuard();
        $jwt = $guard->getToken();

        $this->assertNotNull($guard->user(), $auth->retrieveUser($jwt, $guard->getProvider()));

        $guard->logout(true);

        $this->assertNull($guard->user());
        $this->assertNull($auth->retrieveUser($jwt, $guard->getProvider()));
    }

    public function test_get_claims_it_should_return_empty_array_when_user_is_not_set()
    {
        $guard = $this->createVirginGuard();
        $this->assertEmpty($guard->getClaims());
    }

    public function test_get_claims_it_should_return_claim_array_when_user_is_set()
    {
        $claims = $this->createJwtGuard()->getClaims();
        $this->assertNotEmpty($claims);
    }

    public function test_get_claim_method_it_should_return_null_when_user_is_not_set()
    {
        $guard = $this->createVirginGuard();
        $this->assertNull($guard->getClaim('sub'));
    }

    public function test_get_claim_method_it_should_return_null_when_claim_is_not_in_token()
    {
        $guard = $this->createJwtGuard();
        $this->assertNull($guard->getClaim('sth'));
    }

    public function test_get_claim_method_it_should_return_claim()
    {
        $guard = $this->createJwtGuard();
        $this->assertEquals($guard->user()->getAuthIdentifier(), $guard->getClaim('sub'));
    }
}