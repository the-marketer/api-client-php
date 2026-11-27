<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Subscribers;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;

/**
 * Body of `POST /sms_consent`: a contact's SMS marketing consent, keyed by phone.
 */
class SmsConsent extends AbstractPayload
{
    public const STATE_SUBSCRIBED = 'subscribed';
    public const STATE_UNSUBSCRIBED = 'unsubscribed';

    /** An ISO-8601 date-time with an offset or `Z`, e.g. `2026-11-27T10:15:00Z`, `2026-11-27T12:15:00.123+02:00`. */
    private const ISO_8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})$/';

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $phone,
        #[Assert\Choice(choices: [self::STATE_SUBSCRIBED, self::STATE_UNSUBSCRIBED], message: 'state must be subscribed or unsubscribed.')]
        public string $state,
        #[Assert\Email]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        public ?string $firstname = null,
        #[Assert\Length(max: 255)]
        public ?string $lastname = null,
        public ?string $consent_updated_at = null,
        #[Assert\Length(max: 64)]
        public ?string $source = null,
    ) {}

    /**
     * Strings are trimmed (blank optional ones are left out); `consent_updated_at` also accepts a
     * {@see DateTimeInterface}, sent as ISO-8601 with its offset.
     */
    public static function validateAndCreate(array $data): static
    {
        if (($data['consent_updated_at'] ?? null) instanceof DateTimeInterface) {
            $data['consent_updated_at'] = $data['consent_updated_at']->format(DateTimeInterface::RFC3339_EXTENDED);
        }
        $data = PayloadArrayNormalizer::trimStringFields(
            $data,
            ['phone', 'state', 'email', 'firstname', 'lastname', 'consent_updated_at', 'source'],
        );
        foreach (['email', 'firstname', 'lastname', 'consent_updated_at', 'source'] as $key) {
            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        return parent::validateAndCreate($data);
    }

    #[Assert\Callback]
    public function validateConsentUpdatedAt(ExecutionContextInterface $context): void
    {
        if ($this->consent_updated_at === null) {
            return;
        }

        $valid = preg_match(self::ISO_8601, $this->consent_updated_at) === 1;
        if ($valid) {
            try {
                $date = new DateTimeImmutable($this->consent_updated_at);
                $valid = $date->format('Y-m-d') === substr($this->consent_updated_at, 0, 10);
            } catch (\Exception) {
                $valid = false;
            }
        }

        if (!$valid) {
            $context->buildViolation('consent_updated_at must be an ISO-8601 date-time with an offset, e.g. 2026-11-27T10:15:00Z.')
                ->atPath('consent_updated_at')
                ->addViolation();
        }
    }

    /**
     * @return array<string, string>
     */
    public function toApiPayload(): array
    {
        return ['phone' => $this->phone, 'state' => $this->state] + self::filterNonEmpty([
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'consent_updated_at' => $this->consent_updated_at,
            'source' => $this->source,
        ]);
    }
}
