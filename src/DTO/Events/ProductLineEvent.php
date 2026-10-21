<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Events;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * Product line with variation (product_id, quantity, variation id/sku): same payload shape for
 * cart and wishlist tracking endpoints.
 */
class ProductLineEvent extends AbstractPayload
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
        #[Assert\Positive]
        #[Assert\Type('integer')]
        public int $quantity,
        #[Assert\Valid]
        public ProductLineVariation $variation,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $http_user_agent,
        #[Assert\NotBlank]
        #[Assert\Url]
        public string $url,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $remote_addr,
        #[Assert\Type('string')]
        public ?string $source = null,
        #[Assert\Type('string')]
        public ?string $referrer = null,
    ) {
    }

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::coerceNumericStrings($data, ['product_id', 'quantity'], []);

        $variation = $data['variation'] ?? null;
        if (!is_array($variation)) {
            throw new ValidationException('variation must be an array.');
        }
        $data['variation'] = ProductLineVariation::validateAndCreate($variation);

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
                'quantity' => $this->quantity,
                'variation' => $this->variation->toApiPayload(),
                'http_user_agent' => $this->http_user_agent,
                'url' => $this->url,
                'remote_addr' => $this->remote_addr,
            ],
            self::filterNonEmpty(['source' => $this->source, 'referrer' => $this->referrer]),
        );
    }
}
