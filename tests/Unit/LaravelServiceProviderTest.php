<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Mail\MailManager;
use Tests\TestCase;
use TheMarketer\ApiClient\Client;
use TheMarketer\ApiClient\Laravel\Facades\TheMarketer;
use TheMarketer\ApiClient\Laravel\ApiClientServiceProvider;
use TheMarketer\ApiClient\Laravel\Mail\TheMarketerTransport;

final class LaravelServiceProviderTest extends TestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ApiClientServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('themarketer-api-client', [
            'customerId' => 'laravel-customer',
            'restKey' => 'laravel-rest-key',
            'trackingKey' => 'laravel-tracking-key',
            'restUrl' => 'https://rest.example.test',
            'trackingUrl' => 'https://tracking.example.test',
            'maxRetryAttempts' => 4,
        ]);

        $app['config']->set('mail.from', ['address' => 'global-from@example.test', 'name' => 'Global']);
        $app['config']->set('mail.reply_to', ['address' => 'global-reply@example.test', 'name' => null]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testClientIsResolvedFromContainerUsingPackageConfig(): void
    {
        $client = $this->app->make(Client::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('laravel-customer', $client->config()->customerId());
        $this->assertSame('laravel-rest-key', $client->config()->restKey());
        $this->assertSame('laravel-tracking-key', $client->config()->trackingKey());
        $this->assertSame('https://rest.example.test', $client->config()->restUrl());
        $this->assertSame('https://tracking.example.test', $client->config()->trackingUrl());
    }

    public function testFacadeResolvesClientSingleton(): void
    {
        $this->assertInstanceOf(Client::class, TheMarketer::getFacadeRoot());
        $this->assertSame('laravel-customer', TheMarketer::config()->customerId());
    }

    public function testRegistersTheMarketerMailerTransport(): void
    {
        $mailManager = $this->app->make('mail.manager');
        $this->assertInstanceOf(MailManager::class, $mailManager);

        $transport = $mailManager->mailer('themarketer')->getSymfonyTransport();
        $this->assertInstanceOf(TheMarketerTransport::class, $transport);
    }

    public function testMailerDefaultsComeFromMailConfigNotEnv(): void
    {
        // With `php artisan config:cache`, env() returns null outside config files,
        // so the mailer defaults must be read from the (cached) mail config.
        $this->assertSame(
            ['address' => 'global-from@example.test', 'name' => 'Global'],
            $this->app['config']->get('mail.mailers.themarketer.from'),
        );
        $this->assertSame(
            ['address' => 'global-reply@example.test', 'name' => null],
            $this->app['config']->get('mail.mailers.themarketer.reply_to'),
        );
    }

    public function testExistingMailerConfigOverridesDefaults(): void
    {
        $this->app['config']->set('mail.mailers.themarketer', [
            'transport' => 'themarketer',
            'from' => ['address' => 'custom@example.test'],
        ]);

        $provider = new ApiClientServiceProvider($this->app);
        $provider->boot();

        $this->assertSame(
            ['address' => 'custom@example.test'],
            $this->app['config']->get('mail.mailers.themarketer.from'),
        );
        $this->assertSame(
            ['address' => 'global-reply@example.test', 'name' => null],
            $this->app['config']->get('mail.mailers.themarketer.reply_to'),
        );
    }
}
