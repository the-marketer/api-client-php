<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Events;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class ProductLineVariation extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        public int|string $id,
        #[Assert\NotBlank]
        public int|string $sku,
    ) {
    }
}
