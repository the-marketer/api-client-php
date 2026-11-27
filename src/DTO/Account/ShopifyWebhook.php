<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Account;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use TheMarketer\ApiClient\Common\AbstractPayload;

/**
 * One item of `POST /shopify_webhooks`. The account's webhook is matched on `event_type` + `destination_url` (exact).
 * The scheme of `destination_url` and the presence of `json` depend on the action: see {@see ShopifyWebhooks}.
 */
class ShopifyWebhook extends AbstractPayload
{
    /**
     * @param string      $event_type      a platform webhook event, e.g. `contact_subscribed`, `contact_unsubscribed`,
     *                                     `sms_subscribed`, `sms_unsubscribed`, `contact_updated`
     * @param string      $destination_url where the platform POSTs the event
     * @param string|null $json            the body template, a JSON object; `{{variables}}` such as `{{client_email}}`
     *                                     are allowed as values. Required on upsert, not sent on delete.
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*$/', message: 'event_type must be a webhook event name such as contact_subscribed.')]
        public string $event_type,
        #[Assert\NotBlank]
        #[Assert\Length(max: 2048)]
        public string $destination_url,
        public ?string $json = null,
    ) {}

    #[Assert\Callback]
    public function validateJson(ExecutionContextInterface $context): void
    {
        // The platform's check (as the SPA's SaveWebhookRequest): a JSON object once each {{variable}} reads as 1.
        if ($this->json !== null && !is_object(json_decode((string) preg_replace('/\{\{[^{}]+\}\}/', '1', $this->json)))) {
            $context->buildViolation('json must be a valid JSON object.')
                ->atPath('json')
                ->addViolation();
        }
    }

    /**
     * @return array<string, string>
     */
    public function toApiPayload(bool $withJson = true): array
    {
        $payload = ['event_type' => $this->event_type, 'destination_url' => $this->destination_url];
        if ($withJson && $this->json !== null) {
            $payload['json'] = $this->json;
        }

        return $payload;
    }
}
