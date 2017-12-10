<?php

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use MiladRahimi\LaraJwt\Guards\Jwt;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;

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

    /**
     * @test
     */
    public function it_should_return_a_token()
    {
        $this->assertSame($this->token, $this->guard->getToken());
    }

    /**
     * @test
     */
    public function it_should_return_the_user()
    {
        $this->assertTrue($this->guard->user() instanceof Authenticatable);
    }

    /**
     * @test
     */
    public function is_should_not_be_guest()
    {
        $this->assertFalse($this->guard->guest());
    }

    /**
     * @test
     */
    public function it_should_the_user_id()
    {
        $this->assertEquals($this->user->getAuthIdentifier(), $this->guard->id());
    }

    /**
     * @test
     */
    public function it_should_return_stored_claims()
    {
        $guard = $this->createJwtGuard(['name' => 'Milad', 'surname' => 'Rahimi']);

        $this->assertEquals('Milad', $guard->getClaim('name'));
        $this->assertEquals('Rahimi', $guard->getClaim('surname'));
    }
}