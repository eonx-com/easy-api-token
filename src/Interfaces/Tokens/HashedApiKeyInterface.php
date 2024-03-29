<?php
declare(strict_types=1);

namespace EonX\EasyApiToken\Interfaces\Tokens;

use EonX\EasyApiToken\Interfaces\ApiTokenInterface;

interface HashedApiKeyInterface extends ApiTokenInterface
{
    public const DEFAULT_VERSION = 'v1';

    public const KEY_ID = 'id';

    public const KEY_SECRET = 'secret';

    public const KEY_VERSION = 'version';

    public function getId(): int|string;

    public function getSecret(): string;

    public function getVersion(): string;
}
