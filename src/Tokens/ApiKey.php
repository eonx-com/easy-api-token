<?php

declare(strict_types=1);

namespace EonX\EasyApiToken\Tokens;

use EonX\EasyApiToken\Interfaces\Tokens\ApiKeyInterface;

final class ApiKey implements ApiKeyInterface
{
    /**
     * @var string
     */
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getOriginalToken(): string
    {
        return $this->apiKey;
    }

    /**
     * @return mixed[]
     */
    public function getPayload(): array
    {
        return [
            'api_key' => $this->apiKey,
        ];
    }
}

\class_alias(ApiKey::class, ApiKeyEasyApiToken::class);
