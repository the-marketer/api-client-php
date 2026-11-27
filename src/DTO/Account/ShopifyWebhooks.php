<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Account;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * Body of `POST /shopify_webhooks`, with the platform's rules (ShopifyWebhooksRequest):
 * - `action`: `upsert` or `delete`;
 * - `token`: required on upsert (the Bearer token the platform sends), at most 255 characters; not sent on delete;
 * - `webhooks`: 1 to 20 items; on upsert each needs `json`;
 * - `destination_url`: `https://` only; `http://` too when `allow_http` (the platform accepts it only when it runs
 *   locally, so the client allows it when its REST URL is plain http);
 * - `shop`: optional `*.myshopify.com` domain, remembered by the platform.
 */
class ShopifyWebhooks extends AbstractPayload
{
    public const ACTION_UPSERT = 'upsert';
    public const ACTION_DELETE = 'delete';

    public const MAX_WEBHOOKS = 20;

    /**
     * @param list<ShopifyWebhook> $webhooks
     */
    public function __construct(
        #[Assert\Choice(choices: [self::ACTION_UPSERT, self::ACTION_DELETE])]
        public string $action,
        #[Assert\Count(min: 1, max: self::MAX_WEBHOOKS, minMessage: 'webhooks must not be empty.', maxMessage: 'webhooks must hold at most {{ limit }} items.')]
        #[Assert\Valid]
        public array $webhooks,
        #[Assert\Length(max: 255)]
        public ?string $token = null,
        #[Assert\Regex(pattern: '/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/i', message: 'shop must be a *.myshopify.com domain.')]
        public ?string $shop = null,
        public bool $allow_http = false,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $data['webhooks'] = array_map(
            static fn(mixed $item): ShopifyWebhook => ShopifyWebhook::validateAndCreate(
                is_array($item) ? $item : throw new ValidationException('Each webhook must be an array.'),
            ),
            is_array($data['webhooks'] ?? null) ? array_values($data['webhooks']) : [],
        );

        return parent::validateAndCreate($data);
    }

    #[Assert\Callback]
    public function validateForAction(ExecutionContextInterface $context): void
    {
        $upsert = $this->action === self::ACTION_UPSERT;
        if ($upsert && ($this->token === null || trim($this->token) === '')) {
            $context->buildViolation('token is required on upsert.')->atPath('token')->addViolation();
        }

        $scheme = $this->allow_http ? '~^https?://~i' : '~^https://~i';
        foreach ($this->webhooks as $i => $webhook) {
            if (!$webhook instanceof ShopifyWebhook) {
                continue;
            }
            if (!preg_match($scheme, $webhook->destination_url)) {
                $context->buildViolation($this->allow_http
                    ? 'destination_url must start with http:// or https://.'
                    : 'destination_url must start with https://.')
                    ->atPath("webhooks[$i].destination_url")
                    ->addViolation();
            }
            if ($upsert && ($webhook->json === null || trim($webhook->json) === '')) {
                $context->buildViolation('json is required on upsert.')->atPath("webhooks[$i].json")->addViolation();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $upsert = $this->action === self::ACTION_UPSERT;

        return ['action' => $this->action]
            + ($upsert ? ['token' => $this->token] : [])
            + ['webhooks' => array_map(
                static fn(ShopifyWebhook $webhook): array => $webhook->toApiPayload($upsert),
                $this->webhooks,
            )]
            + self::filterNonEmpty(['shop' => $this->shop]);
    }
}
