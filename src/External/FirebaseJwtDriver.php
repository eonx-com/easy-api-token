<?php

declare(strict_types=1);

namespace EonX\EasyApiToken\External;

use EonX\EasyApiToken\External\Interfaces\JwtDriverInterface;
use Firebase\JWT\JWT;

final class FirebaseJwtDriver implements JwtDriverInterface
{
    /**
     * @var string
     */
    private $algo;

    /**
     * @var string[]
     */
    private $allowedAlgos;

    /**
     * @var null|int
     */
    private $leeway;

    /**
     * @var string|resource
     */
    private $privateKey;

    /**
     * @var string|resource
     */
    private $publicKey;

    /**
     * @param string|resource $publicKey
     * @param string|resource $privateKey
     * @param null|mixed[] $allowedAlgos
     */
    public function __construct(string $algo, $publicKey, $privateKey, ?array $allowedAlgos = null, ?int $leeway = null)
    {
        $this->algo = $algo;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->allowedAlgos = $allowedAlgos ?? [];
        $this->leeway = $leeway;
    }

    public function decode(string $token)
    {
        /**
         * You can add a leeway to account for when there is a clock skew times between
         * the signing and verifying servers. It is recommended that this leeway should
         * not be bigger than a few minutes.
         *
         * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
         */
        if ($this->leeway !== null) {
            JWT::$leeway = $this->leeway;
        }

        /** @var mixed[]|string $publicKey */
        $publicKey = $this->publicKey;

        return JWT::decode($token, $publicKey, $this->allowedAlgos);
    }

    /**
     * @param mixed $input
     */
    public function encode($input): string
    {
        /** @var string $privateKey */
        $privateKey = $this->privateKey;

        return JWT::encode($input, $privateKey, $this->algo);
    }
}
