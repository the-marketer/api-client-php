<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Orders;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;

class SaveOrderProductLine extends AbstractPayload
{
    /**
     * @param int|string $product_id the merchant's product id or the product SKU (the platform looks up both), sent as given
     * @param float|null $sale_price the price actually paid per unit; the platform prefers it to `price` when present
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/\S/', message: 'product_id must not be blank.')]
        #[Assert\NotEqualTo(value: '0', message: 'product_id must not be 0.')]
        public int|string $product_id,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $price,
        #[Assert\Positive]
        public int $quantity,
        #[Assert\Type('string')]
        public ?string $variation_sku = null,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public ?float $sale_price = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::coerceNumericStrings($data, ['quantity'], ['price', 'sale_price']);

        return parent::validateAndCreate($data);
    }

    /** `variation_sku` and `sale_price` are sent when there is one (the platform does not read `variation_sku`; many variants have none). */
    public function toApiPayload(): array
    {
        return self::filterNonEmpty($this->toArray());
    }
}
