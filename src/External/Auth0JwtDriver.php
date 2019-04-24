<?php
declare(strict_types=1);

namespace LoyaltyCorp\EasyApiToken\External;

use Auth0\SDK\API\Helpers\TokenGenerator;
use Auth0\SDK\JWTVerifier;
use LoyaltyCorp\EasyApiToken\External\Interfaces\JwtDriverInterface;

final class Auth0JwtDriver implements JwtDriverInterface
{
    /**
     * @var string[]
     */
    private $allowedAlgos;

    /**
     * @var string
     */
    private $audienceForEncode;

    /**
     * @var string[]
     */
    private $authorizedIss;

    /**
     * @var string|resource
     */
    private $privateKey;

    /**
     * @var string[]
     */
    private $validAudiences;

    /**
     * Auth0JwtDriver constructor.
     *
     * @param string[] $validAudiences
     * @param string[] $authorizedIss
     * @param string|resource $privateKey
     * @param null|string $audienceForEncode
     * @param null|string[] $allowedAlgos
     */
    public function __construct(
        array $validAudiences,
        array $authorizedIss,
        $privateKey,
        ?string $audienceForEncode = null,
        ?array $allowedAlgos = null
    ) {
        $this->validAudiences = $validAudiences;
        $this->authorizedIss = $authorizedIss;
        $this->privateKey = $privateKey;
        $this->audienceForEncode = $audienceForEncode ?? (string)\reset($validAudiences);
        $this->allowedAlgos = $allowedAlgos ?? ['HS256', 'RS256'];
    }

    /**
     * Decode JWT token.
     *
     * @param string $token
     *
     * @return mixed[]|object
     *
     * @throws \Auth0\SDK\Exception\CoreException
     */
    public function decode(string $token)
    {
        $verifier = new JWTVerifier([
            'client_secret' => $this->privateKey,
            'supported_algs' => $this->allowedAlgos,
            'valid_audiences' => $this->validAudiences,
            'authorized_iss' => $this->authorizedIss
        ]);

        return $verifier->verifyAndDecode($token);
    }

    /**
     * Encode given input to JWT token.
     *
     * @param mixed[]|object $input
     *
     * @return string
     */
    public function encode($input): string
    {
        /** @var string $privateKey */
        $privateKey = $this->privateKey;

        $generator = new TokenGenerator($this->audienceForEncode, $privateKey);

        return $generator->generate($input['scopes'] ?? [], $input['lifetime'] ?? TokenGenerator::DEFAULT_LIFETIME);
    }
}

\class_alias(
    Auth0JwtDriver::class,
    'StepTheFkUp\EasyApiToken\External\Auth0JwtDriver',
    false
);
