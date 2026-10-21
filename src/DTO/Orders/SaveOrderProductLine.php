<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Orders;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;

class SaveOrderProductLine extends AbstractPayload
{
    public function __construct(
        #[Assert\Positive]
        public int $product_id,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $price,
        #[Assert\Positive]
        public int $quantity,
        #[Assert\Type('string')]
        public ?string $variation_sku = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::coerceNumericStrings($data, ['product_id', 'quantity'], ['price']);

        return parent::validateAndCreate($data);
    }

    /** `variation_sku` is sent when there is one (the platform does not read it; many variants have none). */
    public function toApiPayload(): array
    {
        return self::filterNonEmpty($this->toArray());
    }
}
