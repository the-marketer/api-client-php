<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Campaigns;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * Body of `POST /campaigns/list`. The platform reads `search`, `type` and `start_date` only inside `filters`;
 * the top-level keys are still accepted and moved into `filters` (a value already in `filters` wins).
 */
class ListCampaign extends AbstractPayload
{
    private const FILTER_KEYS = ['search', 'type', 'start_date'];

    /**
     * @param array<string, mixed>|null $filters an object; a JSON string is decoded first
     */
    public function __construct(
        public ?array $filters = null,
        #[Assert\Type('string')]
        public ?string $search = null,
        #[Assert\Type('string')]
        public ?string $type = null,
        #[Assert\Type('string')]
        public ?string $start_date = null,
        #[Assert\Positive]
        public ?int $page = null,
        #[Assert\Positive]
        public ?int $limit = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $data['filters'] = self::filtersArray($data['filters'] ?? null);
        foreach (['page', 'limit'] as $key) {
            $data[$key] = self::intOrNull($key, $data[$key] ?? null);
        }

        return parent::validateAndCreate($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $filters = $this->filters ?? [];
        foreach (self::FILTER_KEYS as $key) {
            if (!array_key_exists($key, $filters) && $this->{$key} !== null && $this->{$key} !== '') {
                $filters[$key] = $this->{$key};
            }
        }

        return self::filterNonEmpty([
            'page' => $this->page,
            'limit' => $this->limit,
        ]) + ($filters !== [] ? ['filters' => $filters] : []);
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws ValidationException
     */
    private static function filtersArray(mixed $filters): ?array
    {
        if ($filters === null || $filters === '') {
            return null;
        }
        if (is_string($filters)) {
            try {
                $filters = json_decode($filters, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ValidationException('filters: must be an array or a JSON object string.', 400, $e);
            }
        }
        if (!is_array($filters) || ($filters !== [] && array_is_list($filters))) {
            throw new ValidationException('filters: must be an object (an associative array or a JSON object string).');
        }

        return $filters;
    }

    /**
     * @throws ValidationException
     */
    private static function intOrNull(string $key, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\s*-?\d+\s*$/', $value)) {
            return (int) $value;
        }

        throw new ValidationException($key . ': must be an integer.');
    }
}
