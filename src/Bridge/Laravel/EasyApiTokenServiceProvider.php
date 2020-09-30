<?php

declare(strict_types=1);

namespace EonX\EasyApiToken\Bridge\Laravel;

use EonX\EasyApiToken\Bridge\BridgeConstantsInterface;
use EonX\EasyApiToken\Factories\ApiTokenDecoderFactory;
use EonX\EasyApiToken\Interfaces\ApiTokenDecoderInterface;
use EonX\EasyApiToken\Interfaces\ApiTokenDecoderProviderInterface;
use EonX\EasyApiToken\Interfaces\Factories\ApiTokenDecoderFactoryInterface as DecoderFactoryInterface;
use EonX\EasyApiToken\Providers\FromConfigDecoderProvider;
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

        // TODO - Remove in 3.0.
        if (empty(\config('easy-api-token.decoders', [])) === false) {
            @\trigger_error(\sprintf(
                'Defining ApiTokenDecoders using a config file is deprecated since 2.4 and will be removed in 3.0. 
                Use %s instead.',
                ApiTokenDecoderProviderInterface::class
            ), \E_USER_NOTICE);

            $this->app->singleton(FromConfigDecoderProvider::class, function (): FromConfigDecoderProvider {
                return new FromConfigDecoderProvider(
                    \config('easy-api-token.decoders', []),
                    \config('easy-api-token.factories', null),
                    \config('easy-api-token.default_decoder', null)
                );
            });
            $this->app->tag(FromConfigDecoderProvider::class, [BridgeConstantsInterface::TAG_DECODER_PROVIDER]);
        }

        $this->app->singleton(ApiTokenDecoderInterface::class, function (): ApiTokenDecoderInterface {
            return $this->app->make(DecoderFactoryInterface::class)->buildDefault();
        });

        $this->app->singleton(DecoderFactoryInterface::class, function (): DecoderFactoryInterface {
            return new ApiTokenDecoderFactory($this->app->tagged(BridgeConstantsInterface::TAG_DECODER_PROVIDER));
        });
    }
}
