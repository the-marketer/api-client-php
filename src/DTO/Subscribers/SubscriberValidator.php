<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Subscribers;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class SubscriberValidator extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        public ?string $add_tags = null,
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $phone = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $birthday = null,
        public ?string $channels = null,
        public ?array $attributes = null,
        public ?string $name = null,
        public ?string $county = null,
        public ?string $remove_tags = null,
        #[Assert\Choice(choices: [0, 1], message: 'no_double_optin must be 0 or 1.')]
        public ?int $no_double_optin = null,
        #[Assert\Choice(choices: [0, 1], message: 'pretend_double_opt_in_is must be 0 or 1.')]
        public ?int $pretend_double_opt_in_is = null,
    ) {}

    /**
     * `no_double_optin` and `pretend_double_opt_in_is` also accept a bool or `"0"`/`"1"` and are sent as 0/1.
     */
    public static function validateAndCreate(array $data): static
    {
        foreach (['no_double_optin', 'pretend_double_opt_in_is'] as $key) {
            $value = $data[$key] ?? null;
            if (is_bool($value)) {
                $data[$key] = (int) $value;
            } elseif ($value === '0' || $value === '1') {
                $data[$key] = (int) $value;
            } elseif ($value === '') {
                $data[$key] = null;
            }
        }

        return parent::validateAndCreate($data);
    }

    /**
     * Empty fields are left out, except `pretend_double_opt_in_is`, where `0` matters: the platform checks that
     * it is set, then reads it as the account's double opt-in setting for this call (`0` = subscribe now even when
     * the account has double opt-in on). `no_double_optin=1` only stops the confirmation mail. `add_tags` and
     * `remove_tags` are comma-separated tag names.
     */
    public function toApiPayload(): array
    {
        $body = array_merge(
            ['email' => trim($this->email)],
            static::filterNonEmpty([
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'name' => $this->name,
                'add_tags' => $this->add_tags,
                'remove_tags' => $this->remove_tags,
                'phone' => $this->phone,
                'city' => $this->city,
                'county' => $this->county,
                'country' => $this->country,
                'birthday' => $this->birthday,
                'channels' => $this->channels,
                'no_double_optin' => $this->no_double_optin,
                'pretend_double_opt_in_is' => $this->pretend_double_opt_in_is,
            ]),
        );

        if ($this->attributes !== null && $this->attributes !== []) {
            $body['attributes'] = $this->attributes;
        }

        return $body;
    }
}
