<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Loyalty;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class ManageLoyaltyPoints extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['increase', 'decrease'])]
        public string $action,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $points,
    ) {
    }
}