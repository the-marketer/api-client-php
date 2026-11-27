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
     * @param string|null               $source      sent once, in the query string (the body is the list of rows)
     */
    public function __construct(
        #[Assert\Count(min: 1, minMessage: 'subscribers must not be empty.')]
        public array $subscribers,
        #[Assert\Length(max: 64)]
        public ?string $source = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $rows = array_map(
            fn(mixed $item) => SubscriberValidator::validateAndCreate(
                is_array($item) ? $item : throw new ValidationException('Each subscriber must be an array.'),
            ),
            $data['subscribers'] ?? [],
        );

        $source = $data['source'] ?? null;
        if ($source !== null && !is_string($source)) {
            throw new ValidationException('source: must be of type ?string.');
        }

        $instance = new static($rows, $source);
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

    /**
     * @return array<string, string> query parameters: `source` when there is one
     */
    public function toQuery(): array
    {
        return self::filterNonEmpty(['source' => $this->source]);
    }
}