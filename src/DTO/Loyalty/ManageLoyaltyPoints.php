<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Loyalty;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

#[Assert\Expression(
    "(this.email !== null and this.email !== '') or (this.phone !== null and this.phone !== '')",
    message: 'Either email or phone is required.'
)]
class ManageLoyaltyPoints extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['increase', 'decrease'])]
        public string $action,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $points,
        #[Assert\Email]
        public ?string $email = null,
        public ?string $phone = null,
    ) {
    }

    public function toApiPayload(): array
    {
        return self::filterNonEmpty([
            'email' => $this->email,
            'phone' => $this->phone,
            'action' => $this->action,
            'points' => $this->points,
        ]);
    }
}
