<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Reviews;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class ProductReviews extends AbstractPayload
{
    public function __construct(
        #[Assert\Positive]
        public ?int $t = null,
        #[Assert\Positive]
        public ?int $page = null,
        #[Assert\Positive]
        public ?int $perPage = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        foreach (['t', 'page', 'perPage'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            if ($v === null) {
                continue;
            }
            if (is_string($v) && is_numeric($v)) {
                $data[$key] = (int) $v;
            }
        }

        $instance = static::construct($data);
        $instance->validate();

        return $instance;
    }

    /**
     * @return array<string, int>
     */
    public function toApiPayload(): array
    {
        return self::filterNonEmpty([
            't' => $this->t,
            'page' => $this->page,
            'perPage' => $this->perPage,
        ]);
    }
}
