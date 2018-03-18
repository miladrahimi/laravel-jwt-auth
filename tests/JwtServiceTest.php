<?php

namespace MiladRahimi\LaraJwtTests;

/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/18/17
 * Time: 16:00
 */

use Lcobucci\JWT\ValidationData;
use MiladRahimi\LaraJwt\Services\JwtServiceInterface;

class JwtServiceTest extends LaraJwtTestCase
{
    /**
     * @test
     */
    public function it_should_generate_a_token_and_parse_it()
    {
        $key = $this->key();
        $claims = $this->generateClaims();

        $jwtService = app(JwtServiceInterface::class);
        $jwt = $jwtService->generate($claims, $key);
        $parsedClaims = $jwtService->parse($jwt, $key, new ValidationData());

        $this->assertEquals($claims['iss'], $parsedClaims['iss']);
        $this->assertEquals($claims['sub'], $parsedClaims['sub']);
        $this->assertEquals($claims['aud'], $parsedClaims['aud']);
        $this->assertEquals($claims['nbf'], $parsedClaims['nbf']);
        $this->assertEquals($claims['iat'], $parsedClaims['iat']);
        $this->assertEquals($claims['exp'], $parsedClaims['exp']);
        $this->assertEquals($claims['jti'], $parsedClaims['jti']);
    }

    /**
     * Generate testing claims
     *
     * @return array
     */
    private function generateClaims(): array
    {
        $claims = [];
        $claims['iss'] = 'Issuer';
        $claims['sub'] = 'Subject';
        $claims['aud'] = 'The Audience';
        $claims['nbf'] = (string)time();
        $claims['iat'] = (string)time();
        $claims['exp'] = (string)(time() + 60 * 60 * 24);
        $claims['jti'] = (string)mt_rand(1, 999);

        return $claims;
    }

    /**
     * @test
     * @expectedException \MiladRahimi\LaraJwt\Exceptions\InvalidJwtException
     */
    public function it_should_raise_an_error_when_token_is_expired()
    {
        $key = $this->key();
        $claims = $this->generateClaims();

        $claims['exp'] = time() - 1;

        $jwtService = app(JwtServiceInterface::class);
        $jwt = $jwtService->generate($claims, $key);

        $jwtService->parse($jwt, $key, app(ValidationData::class));
    }

    /**
     * @test
     * @expectedException \MiladRahimi\LaraJwt\Exceptions\InvalidJwtException
     */
    public function it_should_raise_an_error_when_token_is_not_valid()
    {
        $key = $this->key();

        $jwt = 'Invalid Token';

        $jwtService = app(JwtServiceInterface::class);
        $jwtService->parse($jwt, $key);
    }
}