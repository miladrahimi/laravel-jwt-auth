<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/18/17
 * Time: 16:00
 */

use Lcobucci\JWT\ValidationData;
use MiladRahimi\LaraJwt\JwtService;
use MiladRahimi\LaraJwt\Values\Token;

class JwtServiceTest extends LaraJwtTestCase
{
    public function test_jwt_service_it_should_generate_a_token_and_parse_it()
    {
        $key = $this->key();

        $token = $this->generateToken();

        $jwt = JwtService::generate($token, $key);

        $validationData = new ValidationData();
        $validationData->setIssuer($token->iss);

        $userToken = JwtService::parse($jwt, $key, $validationData);

        $this->assertEquals($token->iss, $userToken->iss);
        $this->assertEquals($token->sub, $userToken->sub);
        $this->assertEquals($token->aud, $userToken->aud);
        $this->assertEquals($token->nbf, $userToken->nbf);
        $this->assertEquals($token->iat, $userToken->iat);
        $this->assertEquals($token->exp, $userToken->exp);
        $this->assertEquals($token->jti, $userToken->jti);
    }

    private function generateToken(): Token
    {
        $token = new Token();
        $token->iss = 'Issuer';
        $token->sub = 'Subject';
        $token->aud = 'Audiences';
        $token->nbf = time();
        $token->iat = time();
        $token->exp = time() + 60 * 60 * 24;
        $token->jti = mt_rand(1, 999);

        return $token;
    }

    /**
     * @expectedException MiladRahimi\LaraJwt\Exceptions\InvalidJwtException
     */
    public function test_jwt_service_it_should_raise_an_error_when_token_is_expired()
    {
        $key = $this->key();

        $token = $this->generateToken();
        $token->exp = time() - 1;

        $jwt = JwtService::generate($token, $key);

        $validationData = new ValidationData();
        $validationData->setIssuer($token->iss);

        JwtService::parse($jwt, $key, $validationData);
    }

    /**
     * @expectedException MiladRahimi\LaraJwt\Exceptions\InvalidJwtException
     */
    public function test_jwt_service_it_should_raise_an_error_when_token_is_not_valid()
    {
        $key = $this->key();

        $jwt = 'Invalid Token';

        JwtService::parse($jwt, $key);
    }
}