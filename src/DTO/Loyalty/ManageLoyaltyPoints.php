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
        #[Assert\Email]
        public ?string $email,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['increase', 'decrease'])]
        public string $action,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $points,
        public ?string $phone = null,
    ) {
    }

    /**
     * `email` stays the first constructor argument for backwards compatibility,
     * so it has no default; a phone-only payload may leave it out.
     */
    public static function validateAndCreate(array $data): static
    {
        return parent::validateAndCreate(['email' => null, ...$data]);
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
