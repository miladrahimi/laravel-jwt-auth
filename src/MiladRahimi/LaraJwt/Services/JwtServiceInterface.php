<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 11/2/2017 AD
 * Time: 12:38
 */

namespace MiladRahimi\LaraJwt\Services;

use Lcobucci\JWT\ValidationData;
use MiladRahimi\LaraJwt\Exceptions\InvalidJwtException;

interface JwtServiceInterface
{
    /**
     * Generate jwt from the given array of claims
     *
     * @param array $claims
     * @param string $key
     *
     * @return string
     */
    public function generate(array $claims, string $key): string;

    /**
     * Parse (and validate) jwt to extract claims
     *
     * @param string $jwt
     * @param string $key
     * @param ValidationData|null $validationData
     *
     * @return string[]
     */
    public function parse(string $jwt, string $key, ValidationData $validationData = null): array;
}