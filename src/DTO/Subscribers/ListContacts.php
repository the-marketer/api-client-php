<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Subscribers;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

/**
 * Query of `GET /contacts`, with the platform's limits (ContactsV1Request): `page` >= 1, `search` up to 200 characters.
 */
class ListContacts extends AbstractPayload
{
    public const MAX_SEARCH = 200;

    public function __construct(
        #[Assert\Positive(message: 'page must be 1 or more.')]
        public int $page = 1,
        #[Assert\Length(max: self::MAX_SEARCH, maxMessage: 'search must be at most {{ limit }} characters.')]
        public ?string $search = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        if (is_string($data['search'] ?? null)) {
            $data['search'] = trim($data['search']);
        }

        return parent::validateAndCreate($data);
    }

    /** `search` is left out when empty. */
    public function toApiPayload(): array
    {
        return ['page' => $this->page] + self::filterNonEmpty(['search' => $this->search]);
    }
}
