<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 10:59
 */

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\User;
use MiladRahimi\LaraJwt\Services\JwtAuthInterface;

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
    public function it_should_fetch_the_user_from_prev_jwt($info)
    {
        $user = $this->mockUserProvider($info['user']);

        /** @var JwtAuthInterface $jwtAuth */
        $jwtAuth = $this->app[JwtAuthInterface::class];

        $parsedUser = $jwtAuth->fetchUser($info['jwt']);

        $this->assertEquals($user->getAuthIdentifier(), $parsedUser->getAuthIdentifier());
    }

    /**
     * Mock User Provider service
     *
     * @param User $user
     * @return User
     */
    private function mockUserProvider(User $user): User
    {
        $userProviderMock = Mockery::mock(UserProvider::class)
            ->shouldReceive('retrieveById')->withArgs([$user->getAuthIdentifier()])
            ->andReturn($user)
            ->getMock();

        $this->app[UserProvider::class] = $userProviderMock;

        return $user;
    }

    /**
     * Generate a brand new user
     *
     * @return User
     */
    private function generateUser(): User
    {
        $id = $this->faker->numberBetween(1, 1000);

        $user = app(User::class);

        $idFieldName = $user->getAuthIdentifierName();
        $user->setAttribute($idFieldName, $id);

        return $user;
    }
}