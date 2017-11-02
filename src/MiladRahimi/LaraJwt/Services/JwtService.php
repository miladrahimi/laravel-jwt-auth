<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/18/17
 * Time: 15:46
 */

namespace MiladRahimi\LaraJwt\Services;

use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\ValidationData;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;

class JwtService implements JwtServiceInterface
{
    /**
     * @inheritdoc
     */
    public function generate(array $claims, string $key): string
    {
        /** @var Builder $jwtBuilder */
        $jwtBuilder = app(Builder::class);

        foreach ($claims as $name => $value) {
            $jwtBuilder->set((string)$name, (string)$value);
        }

        $jwt = $jwtBuilder->sign(new Sha512(), $key)->getToken();

        return $jwt;
    }

    /**
     * @inheritdoc
     */
    public function parse(string $jwt, string $key, ValidationData $validationData = null): array
    {
        /** @var Parser $parser */
        $parser = app(Parser::class);

        /** @var Sha512 $algorithm */
        $algorithm = app(Sha512::class);

        try {
            $data = $parser->parse($jwt);

            if ($validationData && $data->validate($validationData) == false) {
                throw new InvalidJwtException('Jwt validation error');
            }

            if ($data->verify($algorithm, $key) == false) {
                throw new InvalidJwtException('Jwt verification error');
            }

            $claims = $data->getClaims();
        } catch (Exception $e) {
            throw new InvalidJwtException($e->getMessage(), 0, $e);
        }

        return $claims;
    }
}