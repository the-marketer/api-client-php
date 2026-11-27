<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Orders;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

/**
 * The variant bought on an order line (`products[].variation`). The platform stores it as sent.
 */
class SaveOrderProductVariation extends AbstractPayload
{
    /**
     * @param int|string      $id  the variant id, sent as given
     * @param int|string|null $sku the variant SKU; many variants have none
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/\S/', message: 'variation.id must not be blank.')]
        public int|string $id,
        public int|string|null $sku = null,
    ) {}

    /** `sku` is left out when there is none. */
    public function toApiPayload(): array
    {
        return ['id' => $this->id] + self::filterNonEmpty(['sku' => $this->sku]);
    }
}
