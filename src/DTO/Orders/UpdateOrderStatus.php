<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Orders;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class UpdateOrderStatus extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/\S/', message: 'order_number must not be blank.')]
        public int|string $order_number,
        #[Assert\NotBlank]
        public string $order_status,
    ) {}
}
