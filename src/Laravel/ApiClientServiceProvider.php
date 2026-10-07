<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\Laravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use TheMarketer\ApiClient\Client;
use TheMarketer\ApiClient\Laravel\Mail\TheMarketerTransport;

class ApiClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/themarketer-api-client.php', 'themarketer-api-client');

        $this->app->singleton(Client::class, static function ($app): Client {
            /** @var array{
             *     customerId: string,
             *     restKey: string,
             *     trackingKey?: string,
             *     restUrl?: string,
             *     trackingUrl?: string,
             *     maxRetryAttempts?: int
             * } $config
             */
            $config = (array)$app['config']->get('themarketer-api-client', []);

            return new Client($config);
        });

        $this->app->alias(Client::class, 'themarketer.api-client');
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/themarketer-api-client.php' => $this->app->configPath('themarketer-api-client.php'),
        ], 'themarketer-api-client-config');

        $this->registerTheMarketerMailerConfig();

        $this->app->afterResolving('mail.manager', function ($manager): void {
            if (!$manager instanceof MailManager) {
                return;
            }

            $manager->extend('themarketer', function (array $config = []): TheMarketerTransport {
                return new TheMarketerTransport($this->app->make(Client::class), $config);
            });
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerTheMarketerMailerConfig(): void
    {
        $config = $this->app->make('config');
        $mailers = (array)$config->get('mail.mailers', []);

        // Read from mail config, not env(): env() returns null once config is cached.
        $defaults = array_filter([
            'transport' => 'themarketer',
            'from' => $config->get('mail.from'),
            'reply_to' => $config->get('mail.reply_to'),
        ], static fn($value): bool => $value !== null);

        if (array_key_exists('themarketer', $mailers)) {
            /** @var array<string, mixed> $existing */
            $existing = (array)$mailers['themarketer'];
            $mailers['themarketer'] = array_merge($defaults, $existing);
        } else {
            $mailers['themarketer'] = $defaults;
        }

        $config->set('mail.mailers', $mailers);
    }
}
