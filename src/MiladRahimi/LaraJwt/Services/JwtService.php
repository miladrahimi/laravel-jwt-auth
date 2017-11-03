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
use Lcobucci\JWT\Claim\EqualsTo;
use Lcobucci\JWT\Parser;
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
            $jwtBuilder->set($name, $value);
        }

        $algorithm = app('larajwt.signer');

        $jwt = $jwtBuilder->sign($algorithm, $key)->getToken();

        return $jwt;
    }

    /**
     * @inheritdoc
     */
    public function parse(string $jwt, string $key, ValidationData $validationData = null): array
    {
        /** @var Parser $parser */
        $parser = app(Parser::class);
        $algorithm = app('larajwt.signer');

        try {
            $data = $parser->parse($jwt);

            if ($validationData && $data->validate($validationData) == false) {
                throw new InvalidJwtException('Jwt validation error');
            }

            if ($data->verify($algorithm, $key) == false) {
                throw new InvalidJwtException('Jwt verification error');
            }

            $claims = [];

            /** @var EqualsTo $claim */
            foreach ($data->getClaims() as $claim) {
                $claims[$claim->getName()] = $claim->getValue();
            }
        } catch (Exception $e) {
            throw new InvalidJwtException($e->getMessage(), 0, $e);
        }

        return $claims;
    }
}