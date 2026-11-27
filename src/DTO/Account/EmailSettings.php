<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Account;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

/**
 * Query of `GET /settings/email`: `domain` is the index of an extra sender domain; null = the account's main domain.
 */
class EmailSettings extends AbstractPayload
{
    public function __construct(
        #[Assert\PositiveOrZero]
        public ?int $domain = null,
    ) {}

    public function toApiPayload(): array
    {
        return self::filterNonEmpty(['domain' => $this->domain]);
    }
}
