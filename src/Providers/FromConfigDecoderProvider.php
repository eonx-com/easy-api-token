<?php

declare(strict_types=1);

namespace EonX\EasyApiToken\Providers;

use EonX\EasyApiToken\Exceptions\InvalidConfigurationException;
use EonX\EasyApiToken\Interfaces\ApiTokenDecoderInterface;
use EonX\EasyApiToken\Interfaces\Factories\ApiTokenDecoderFactoryInterface;
use EonX\EasyApiToken\Interfaces\Factories\ApiTokenDecoderSubFactoryInterface;
use EonX\EasyApiToken\Interfaces\Factories\DecoderNameAwareInterface;
use EonX\EasyApiToken\Interfaces\Factories\MasterDecoderFactoryAwareInterface;
use EonX\EasyApiToken\Traits\DefaultDecoderFactoriesTrait;
use Psr\Container\ContainerInterface;

final class FromConfigDecoderProvider extends AbstractApiTokenDecoderProvider implements ApiTokenDecoderFactoryInterface
{
    use DefaultDecoderFactoriesTrait;

    /**
     * @var mixed[]
     */
    private $config;

    /**
     * @var \Psr\Container\ContainerInterface
     */
    private $container;

    /**
     * @var null|string
     */
    private $defaultDecoder;

    /**
     * @var string[]
     */
    private $defaultFactories;

    /**
     * @var mixed[]
     */
    private $resolved = [];

    /**
     * @param mixed[] $config
     * @param null|string[] $defaultFactories
     *
     * @throws \EonX\EasyApiToken\Exceptions\InvalidConfigurationException
     */
    public function __construct(
        array $config,
        ?array $defaultFactories = null,
        ?string $defaultDecoder = null,
        ?int $priority = null
    ) {
        if (empty($config)) {
            throw new InvalidConfigurationException('No decoders configured');
        }

        $this->config = $config;
        $this->defaultFactories = $defaultFactories ?? $this->getDefaultDecoderFactories();
        $this->defaultDecoder = $defaultDecoder;

        parent::__construct($priority);
    }

    public function build(?string $decoder = null): ApiTokenDecoderInterface
    {
        if ($decoder === null) {
            return $this->buildDefault();
        }

        if (isset($this->resolved[$decoder])) {
            return $this->resolved[$decoder];
        }

        if (empty($this->config) || \array_key_exists($decoder, $this->config) === false) {
            throw new InvalidConfigurationException(\sprintf('No decoder configured for key: "%s".', $decoder));
        }

        $config = $this->config[$decoder] ?? [];
        $subFactory = $this->instantiateSubFactory($decoder, $config);

        if ($subFactory instanceof DecoderNameAwareInterface) {
            $subFactory->setDecoderName($decoder);
        }
        if ($subFactory instanceof MasterDecoderFactoryAwareInterface) {
            $subFactory->setMasterFactory($this);
        }

        return $this->resolved[$decoder] = $subFactory->build($config, $decoder);
    }

    public function buildDefault(): ApiTokenDecoderInterface
    {
        return $this->build($this->defaultDecoder);
    }

    /**
     * @return iterable<\EonX\EasyApiToken\Interfaces\ApiTokenDecoderInterface>
     *
     * @throws \EonX\EasyApiToken\Exceptions\InvalidConfigurationException
     */
    public function getDecoders(): iterable
    {
        foreach (\array_keys($this->config) as $decoder) {
            yield $this->build($decoder);
        }
    }

    public function getDefaultDecoder(): ?string
    {
        return $this->defaultDecoder;
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @param mixed[] $config
     *
     * @throws \EonX\EasyApiToken\Exceptions\InvalidConfigurationException
     */
    private function getSubFactoryClass(string $decoder, array $config): string
    {
        // If explicit type is set, use it
        if (empty($config['type']) === false && \is_string($config['type'])) {
            $type = $config['type'];

            // Allow type to be alias of default factory
            return empty($this->defaultFactories[$type]) === false ? $this->defaultFactories[$type] : $type;
        }

        // Default to factory for decoder
        if (empty($this->defaultFactories[$decoder]) === false) {
            return $this->defaultFactories[$decoder];
        }

        throw new InvalidConfigurationException(\sprintf(
            'No "type" or default factory configured for decoder "%s".',
            $decoder
        ));
    }

    /**
     * @param mixed[] $config
     *
     * @throws \EonX\EasyApiToken\Exceptions\InvalidConfigurationException
     */
    private function instantiateSubFactory(string $decoder, array $config): ApiTokenDecoderSubFactoryInterface
    {
        $factoryClass = $this->getSubFactoryClass($decoder, $config);

        try {
            if ($this->container !== null && $this->container->has($factoryClass)) {
                return $this->container->get($factoryClass);
            }

            if (\class_exists($factoryClass)) {
                return new $factoryClass();
            }
        } catch (\Throwable $exception) {
            throw new InvalidConfigurationException(\sprintf(
                'Unable to instantiate the factory "%s" for decoder "%s": %s',
                $factoryClass,
                $decoder,
                $exception->getMessage()
            ), $exception->getCode(), $exception);
        }

        throw new InvalidConfigurationException(\sprintf(
            'Unable to instantiate the factory "%s" for decoder "%s".',
            $factoryClass,
            $decoder
        ));
    }
}
