<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/18/17
 * Time: 15:46
 */

namespace MiladRahimi\LaraJwt;

use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\ValidationData;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;
use MiladRahimi\LaraJwt\Values\Token;

class JwtService
{
    /**
     * Generate jwt from token object
     *
     * @param \MiladRahimi\LaraJwt\Values\Token $token
     * @param string $key
     * @return string
     */
    public static function generate(Token $token, string $key): string
    {
        /** @var Builder $builder */
        $builder = app(Builder::class);

        $jwt = $builder
            ->setIssuer($token->iss)
            ->setSubject($token->sub)
            ->setAudience($token->aud)
            ->setIssuedAt($token->iat)
            ->setNotBefore($token->nbf)
            ->setExpiration($token->exp)
            ->setId($token->jti)
            ->sign(new Sha512(), $key)
            ->getToken();

        return $jwt;
    }

    /**
     * @param string $jwt
     * @param string $key
     * @param ValidationData|null $validationData
     * @return Token
     * @throws InvalidJwtException
     */
    public static function parse(string $jwt, string $key, ValidationData $validationData = null): Token
    {
        /** @var Parser $parser */
        $parser = app(Parser::class);

        try {
            $data = $parser->parse($jwt);

            if ($validationData && $data->validate($validationData) == false) {
                throw new InvalidJwtException();
            }

            if ($data->verify(new Sha512(), $key) == false) {
                throw new InvalidJwtException();
            }
        } catch (Exception $e) {
            throw new InvalidJwtException('', 0, $e);
        }

        $token = new Token();
        $token->iss = $data->getClaim('iss');
        $token->sub = $data->getClaim('sub');
        $token->aud = $data->getClaim('aud');
        $token->iat = $data->getClaim('iat');
        $token->nbf = $data->getClaim('nbf');
        $token->exp = $data->getClaim('exp');
        $token->jti = $data->getClaim('jti');

        return $token;
    }
}