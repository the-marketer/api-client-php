<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Subscribers;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Exception\ValidationException;

class AddSubscriberBulk extends AbstractPayload
{
    /**
     * @param list<SubscriberValidator> $subscribers
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'subscribers must not be empty.')]
        public array $subscribers,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $rows = array_map(
            fn(mixed $item) => SubscriberValidator::validateAndCreate(
                is_array($item) ? $item : throw new ValidationException('Each subscriber must be an array.'),
            ),
            $data['subscribers'] ?? [],
        );

        $instance = new static($rows);
        $instance->validate();

        return $instance;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toApiPayload(): array
    {
        return array_map(
            static fn(SubscriberValidator $row): array => $row->toApiPayload(),
            $this->subscribers,
        );
    }
}