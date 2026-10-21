<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\Common;

use GuzzleHttp\Client as GuzzleClient;
use TheMarketer\ApiClient\Gateways\AbstractGateway;
use TheMarketer\ApiClient\Gateways\ApiGateway;
use TheMarketer\ApiClient\Gateways\TrackingGateway;

/**
 * Extension point: pass your own Guzzle client as `$httpClient` to control timeouts, retries and
 * proxies, or to answer with a `MockHandler` in tests. It replaces the default client, so it also
 * replaces the default retry handler.
 *
 * @property-read ApiGateway $rest
 * @property-read TrackingGateway $tracking
 */
class ApiContext
{
    private array $gateways = [];

    public function __construct(
        public readonly Config $config,
        private readonly int $maxRetryAttempts = 1,
        private readonly ?GuzzleClient $httpClient = null,
    ) {}

    public function __get(string $name): AbstractGateway
    {
        return $this->gateways[$name] ??= match($name) {
            'rest'     => new ApiGateway($this->config, $this->maxRetryAttempts, $this->httpClient),
            'tracking' => new TrackingGateway($this->config, $this->maxRetryAttempts, $this->httpClient),
            default    => throw new \InvalidArgumentException("Unknown gateway: $name"),
        };
    }

    public function tracking(): TrackingGateway
    {
        return $this->gateways['tracking'] ??= new TrackingGateway($this->config, $this->maxRetryAttempts, $this->httpClient);
    }
}
