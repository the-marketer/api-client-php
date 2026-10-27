<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Events;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * Wishlist tracking: only `product_id` is required; `quantity` and `variation` are sent when present.
 */
class WishlistEvent extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $did,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $event,
        #[Assert\NotBlank]
        public int|string $product_id,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $http_user_agent,
        #[Assert\NotBlank]
        #[Assert\Url]
        public string $url,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $remote_addr,
        #[Assert\Positive]
        #[Assert\Type('integer')]
        public ?int $quantity = null,
        #[Assert\Valid]
        public ?ProductLineVariation $variation = null,
        #[Assert\Type('string')]
        public ?string $source = null,
        #[Assert\Type('string')]
        public ?string $referrer = null,
    ) {
    }

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::coerceNumericStrings($data, ['quantity'], []);

        if (($data['variation'] ?? null) !== null) {
            if (!is_array($data['variation'])) {
                throw new ValidationException('variation must be an array.');
            }
            $data['variation'] = ProductLineVariation::validateAndCreate($data['variation']);
        }

        return parent::validateAndCreate($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        return array_merge(
            [
                'did' => $this->did,
                'event' => $this->event,
                'product_id' => $this->product_id,
                'http_user_agent' => $this->http_user_agent,
                'url' => $this->url,
                'remote_addr' => $this->remote_addr,
            ],
            self::filterNonEmpty([
                'quantity' => $this->quantity,
                'variation' => $this->variation?->toApiPayload(),
                'source' => $this->source,
                'referrer' => $this->referrer,
            ]),
        );
    }
}
