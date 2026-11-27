<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Subscribers;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class RemoveSubscriber extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        public ?string $channels = null,
        #[Assert\Length(max: 64)]
        public ?string $source = null,
    ) {
    }

    /** `source` (e.g. `connector`) is sent when there is one. */
    public function toApiPayload(): array
    {
        return self::filterNonEmpty(['email' => $this->email, 'channels' => $this->channels, 'source' => $this->source]);
    }
}
