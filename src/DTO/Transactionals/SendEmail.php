<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Transactionals;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class SendEmail extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $to,
        #[Assert\NotBlank]
        public string $subject,
        #[Assert\NotBlank]
        public string $body,
        #[Assert\Type('string')]
        public ?string $from = null,
        #[Assert\Email]
        public ?string $reply_to = null,
        #[Assert\Type('array')]
        public ?array $attachments = null,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        foreach (['to', 'from', 'reply_to'] as $key) {
            if (!array_key_exists($key, $data) || !is_string($data[$key])) {
                continue;
            }
            $trimmed = trim($data[$key]);
            $data[$key] = $trimmed;
            if ($key !== 'to' && $trimmed === '') {
                $data[$key] = null;
            }
        }

        $instance = static::construct($data);
        $instance->validate();

        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $optional = self::filterNonEmpty([
            'from' => $this->from,
            'reply_to' => $this->reply_to,
        ]);

        if ($this->attachments !== null && $this->attachments !== []) {
            $optional['attachments'] = $this->attachments;
        }

        return array_merge(
            [
                'to' => $this->to,
                'subject' => $this->subject,
                'body' => $this->body,
            ],
            $optional,
        );
    }
}
