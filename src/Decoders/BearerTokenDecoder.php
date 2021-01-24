<?php

declare(strict_types=1);

namespace EonX\EasyApiToken\Decoders;

use EonX\EasyApiToken\External\Interfaces\JwtDriverInterface;
use EonX\EasyApiToken\Interfaces\ApiTokenInterface;
use EonX\EasyApiToken\Tokens\Jwt;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class BearerTokenDecoder extends AbstractApiTokenDecoder
{
    /**
     * @var \EonX\EasyApiToken\External\Interfaces\JwtDriverInterface
     */
    private $jwtDriver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(JwtDriverInterface $jwtDriver, ?string $name = null, ?LoggerInterface $logger = null)
    {
        $this->jwtDriver = $jwtDriver;
        $this->logger = $logger ?? new NullLogger();

        parent::__construct($name);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request|\Psr\Http\Message\ServerRequestInterface $request
     */
    public function decode($request): ?ApiTokenInterface
    {
        $authorization = $this->getHeaderWithoutPrefix('Authorization', 'Bearer', $request);

        if ($authorization === null) {
            // If Authorization doesn't start with Basic, return null
            return null;
        }

        try {
            return new Jwt((array)$this->jwtDriver->decode(\trim($authorization)), $authorization);
        } catch (\Throwable $throwable) {
            $this->logger->info(\sprintf('Invalid JWT token from request: "%s"', $throwable->getMessage()));

            // Return null not to break chain decoder
            return null;
        }
    }
}
