<?php

declare(strict_types=1);

namespace EonX\EasyApiToken\Bridge\Laravel;

use EonX\EasyApiToken\Bridge\BridgeConstantsInterface;
use EonX\EasyApiToken\Factories\ApiTokenDecoderFactory;
use EonX\EasyApiToken\Interfaces\ApiTokenDecoderInterface;
use EonX\EasyApiToken\Interfaces\Factories\ApiTokenDecoderFactoryInterface as DecoderFactoryInterface;
use Illuminate\Support\ServiceProvider;

final class EasyApiTokenServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/easy-api-token.php' => \base_path('config/easy-api-token.php'),
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/easy-api-token.php', 'easy-api-token');

        $this->app->singleton(ApiTokenDecoderInterface::class, function (): ApiTokenDecoderInterface {
            return $this->app->make(DecoderFactoryInterface::class)->buildDefault();
        });

        $this->app->singleton(DecoderFactoryInterface::class, function (): DecoderFactoryInterface {
            return new ApiTokenDecoderFactory($this->app->tagged(BridgeConstantsInterface::TAG_DECODER_PROVIDER));
        });
    }
}
