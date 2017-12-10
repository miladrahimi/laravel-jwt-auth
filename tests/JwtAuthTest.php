<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 10:59
 */

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;
use MiladRahimi\LaraJwt\Services\JwtServiceInterface;

class JwtAuthTest extends LaraJwtTestCase
{
    /**
     * @test
     * @return array
     */
    public function it_should_generate_a_valid_token()
    {
        $user = $this->generateUser();

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwt = $jwtAuth->generateToken($user);

        $this->assertNotNull($jwt);

        return ['jwt' => $jwt, 'user' => $user];
    }

    /**
     * @test
     * @depends it_should_generate_a_valid_token
     * @param array $info
     */
    public function it_should_retrieve_the_user_from_prev_jwt($info)
    {
        $user = $this->mockUserProvider($info['user']);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $parsedUser = $jwtAuth->retrieveUser($info['jwt']);

        $this->assertEquals($user->getAuthIdentifier(), $parsedUser->getAuthIdentifier());
    }

    /**
     * @test
     * @depends it_should_generate_a_valid_token
     * @param array $info
     * @throws \MiladRahimi\LaraJwt\Exceptions\InvalidJwtException
     */
    public function it_should_retrieve_claims_from_jwt($info)
    {
        /** @var User $user */
        $user = $info['user'];

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $claims = $jwtAuth->retrieveClaims($info['jwt']);

        $this->assertEquals($user->getAuthIdentifier(), $claims['sub']);
        $this->assertEquals($this->app['config']->get('jwt.issuer'), $claims['iss']);
        $this->assertEquals($this->app['config']->get('jwt.audience'), $claims['aud']);
    }

    /**
     * @test
     * @depends it_should_generate_a_valid_token
     * @param array $info
     */
    public function it_should_recognize_jwt_is_valid($info)
    {
        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(true, $jwtAuth->isTokenValid($info['jwt']));
    }

    /**
     * @test
     */
    public function it_should_say_the_jwt_is_invalid_when_it_is_not_valid()
    {
        $jwt = 'Shit';

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(false, $jwtAuth->isTokenValid($jwt));
    }

    /**
     * @test
     */
    public function it_should_say_the_jwt_is_invalid_when_it_is_corrupted()
    {
        /** @var JwtServiceInterface $jwtService */
        $jwtService = $this->app[JwtServiceInterface::class];

        $jwt = $jwtService->generate(['sub' => 666], $this->key());
        $jwt = substr($jwt, 0, strpos($jwt, '.'));

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(false, $jwtAuth->isTokenValid($jwt));
    }

    /**
     * @test
     */
    public function it_should_run_post_hooks_when_there_some_post_hooks_registered()
    {
        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwtAuth->registerPostHook(function (Authenticatable $u) {
            $u->setRememberToken('some_token');
        });

        $user = $this->generateUser();

        $jwtAuth->runPostHooks($user);

        $this->assertEquals('some_token', $user->getRememberToken());
    }

    /**
     * @test
     * @expectedException Exception
     */
    public function it_should_run_post_hooks_and_throw_exception_when_there_some_post_hooks_registered()
    {
        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwtAuth->registerPostHook(function (Authenticatable $u) {
            throw new Exception($u);
        });

        $user = $this->generateUser();

        $jwtAuth->runPostHooks($user);
    }

    /**
     * @test
     */
    public function it_should_say_the_jwt_is_invalid_when_it_has_not_sub_claim()
    {
        /** @var JwtServiceInterface $jwtService */
        $jwtService = $this->app[JwtServiceInterface::class];

        $jwt = $jwtService->generate([], $this->key());

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(false, $jwtAuth->isTokenValid($jwt));
    }

    /**
     * @test
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function it_should_set_logout_user()
    {
        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $user = $this->generateUser();

        $time = time();

        $jwtAuth->logout($user);

        $cached = app('cache')->get("jwt:users:{$user->getAuthIdentifier()}:logout");

        $this->assertLessThanOrEqual($time, $cached);

        $this->assertGreaterThanOrEqual(time(), $cached);
    }
}