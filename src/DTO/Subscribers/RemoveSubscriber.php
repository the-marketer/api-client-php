<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Subscribers;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

#[Assert\Expression(
    "(this.email !== null and this.email !== '') or (this.phone !== null and this.phone !== '')",
    message: 'Either email or phone is required.'
)]
class RemoveSubscriber extends AbstractPayload
{
    public function __construct(
        #[Assert\Email]
        public ?string $email = null,
        public ?string $channels = null,
        public ?string $phone = null,
    ) {
    }

    public function toApiPayload(): array
    {
        return self::filterNonEmpty([
            'email' => $this->email,
            'phone' => $this->phone,
            'channels' => $this->channels,
        ]);
    }
}
