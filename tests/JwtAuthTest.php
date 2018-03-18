<?php

namespace MiladRahimi\LaraJwtTests;

/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 10:59
 */

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use MiladRahimi\LaraJwt\Services\JwtAuth;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;
use MiladRahimi\LaraJwt\Services\JwtServiceInterface;
use MiladRahimi\LaraJwtTests\Classes\Person;
use MiladRahimi\LaraJwtTests\Classes\SomeException;

class JwtAuthTest extends LaraJwtTestCase
{
    public function test_generate_token_method_it_should_return_a_token()
    {
        $user = $this->generateUser();

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwt = $jwtAuth->generateToken($user);

        $this->assertNotNull($jwt);

        return ['jwt' => $jwt, 'user' => $user];
    }

    private function generateUserAndJwt(): array
    {
        $user = $this->generateUser();

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwt = $jwtAuth->generateToken($user);

        return [$user, $jwt];
    }

    
    public function test_retrieve_user_method_it_should_return_the_user_model()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertEquals($user->getAuthIdentifier(), $parsedUser->getAuthIdentifier());
    }

    public function test_retrieve_user_method_it_should_not_return_user_when_jwt_is_invalid()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $parsedUser = $jwtAuth->retrieveUser($jwt . 'Invalidator');

        $this->assertNull($parsedUser);
    }

    public function test_retrieve_user_method_it_should_not_return_user_when_model_is_not_valid_on_model_safe_mode()
    {
        $this->app['config']->set('jwt.model_safe', true);

        $person = new Person();
        $person->setAttribute($person->getAuthIdentifierName(), 666);

        $user = app(User::class);
        $user->setAttribute($user->getAuthIdentifierName(), 666);

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwt = $jwtAuth->generateToken($person);
        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertNull($parsedUser);
    }

    public function test_retrieve_user_method_it_should_return_user_when_model_is_not_valid_out_of_model_safe_mode()
    {
        $this->app['config']->set('jwt.model_safe', false);

        $person = new Person();
        $person->setAttribute($person->getAuthIdentifierName(), 666);

        $user = app(User::class);
        $user->setAttribute($user->getAuthIdentifierName(), 666);

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwt = $jwtAuth->generateToken($person);
        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertEquals($user->getAuthIdentifier(), $parsedUser->getAuthIdentifier());
    }

    public function test_retrieve_user_method_it_should_not_return_user_when_jwt_is_expired()
    {
        $this->app['config']->set('jwt.ttl', -100);

        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertNull($parsedUser);
    }

    /**
     * @throws \MiladRahimi\LaraJwt\Exceptions\LaraJwtConfiguringException
     */
    public function test_retrieve_user_method_it_should_not_return_user_when_issuer_is_not_the_same()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        $this->app['config']->set('jwt.issuer', 'Some thing new!');

        $jwtAuth = new JwtAuth();

        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertNull($parsedUser);
    }

    /**
     * @throws \MiladRahimi\LaraJwt\Exceptions\LaraJwtConfiguringException
     */
    public function test_retrieve_user_method_it_should_not_return_user_when_audience_is_not_the_same()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        $this->app['config']->set('jwt.audience', 'Some thing new!');

        $jwtAuth = new JwtAuth();

        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertNull($parsedUser);
    }

    public function test_retrieve_user_method_it_should_not_return_user_when_his_jwt_is_invalidated()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $claims = $jwtAuth->retrieveClaims($jwt);
        $jwtAuth->invalidate($claims['jti']);
        $parsedUser = $jwtAuth->retrieveUser($jwt);

        $this->assertNull($parsedUser);
    }
    
    public function test_retrieve_claims_method_it_should_retrieve_claims_from_jwt()
    {
        /** @var User $user */
       list($user, $jwt) = $this->generateUserAndJwt();

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $claims = $jwtAuth->retrieveClaims($jwt);

        $this->assertEquals($user->getAuthIdentifier(), $claims['sub']);
        $this->assertEquals($this->app['config']->get('jwt.issuer'), $claims['iss']);
        $this->assertEquals($this->app['config']->get('jwt.audience'), $claims['aud']);
    }

    public function test_is_jwt_valid_method_it_should_recognize_jwt_is_valid()
    {
        $jwt = $this->generateUserAndJwt()[1];

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(true, $jwtAuth->isJwtValid($jwt));
    }
    
    public function test_is_jwt_valid_method_it_should_say_the_jwt_is_invalid_when_it_is_not_valid()
    {
        $jwt = 'Shit';

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(false, $jwtAuth->isJwtValid($jwt));
    }
    
    public function test_is_jwt_valid_method_it_should_say_the_jwt_is_invalid_when_it_is_corrupted()
    {
        /** @var JwtServiceInterface $jwtService */
        $jwtService = $this->app[JwtServiceInterface::class];

        $jwt = $jwtService->generate(['sub' => 666], $this->key());
        $jwt = substr($jwt, 0, strpos($jwt, '.'));

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(false, $jwtAuth->isJwtValid($jwt));
    }

    public function test_is_jwt_valid_method_it_should_say_the_jwt_is_invalid_when_it_has_not_sub_claim()
    {
        /** @var JwtServiceInterface $jwtService */
        $jwtService = $this->app[JwtServiceInterface::class];

        $jwt = $jwtService->generate([], $this->key());

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $this->assertEquals(false, $jwtAuth->isJwtValid($jwt));
    }
    
    public function test_filters_it_should_when_there_are_some_registered_filters()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwtAuth->registerFilter(function (Authenticatable $u) {
            $u->setRememberToken('some_token');
            return $u;
        });

        $user = $jwtAuth->retrieveUser($jwt);

        $this->assertEquals('some_token', $user->getRememberToken());
    }

    /**
     * @test
     * @expectedException \MiladRahimi\LaraJwtTests\Classes\SomeException
     */
    public function test_filters_it_should_throw_exception_when_there_is_a_filter_that_throws_an_exception()
    {
        list($user, $jwt) = $this->generateUserAndJwt();

        $this->mockUserProvider($user);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jwtAuth->registerFilter(function (Authenticatable $u) {
            throw new SomeException($u);
        });

        $jwtAuth->retrieveUser($jwt);
    }

    /**
     * @test
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function test_invalidate_token_it_should_invalidate_token()
    {
        $jwt = $this->generateUserAndJwt()[1];

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $jti = $jwtAuth->retrieveClaims($jwt)['jti'];

        $jwtAuth->invalidate($jti);

        $time = time();

        $cached = app('cache')->get("jwt:invalidated:$jti");

        $this->assertLessThanOrEqual($time, $cached);

        $this->assertGreaterThanOrEqual(time(), $cached);
    }
}