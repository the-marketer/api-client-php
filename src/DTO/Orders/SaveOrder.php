<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Orders;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;
use TheMarketer\ApiClient\Exception\ValidationException;

class SaveOrder extends AbstractPayload
{
    /**
     * @param int|string                 $number  an order number: numeric, a draft number such as `D1`, or any text such as `EU-1001`
     * @param list<SaveOrderProductLine> $products
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/\S/', message: 'number must not be blank.')]
        #[Assert\NotEqualTo(value: '0', message: 'number must not be 0.')]
        public int|string $number,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $firstname,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $lastname,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $discount_value,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $shipping,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $tax,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $total_value,
        #[Assert\Count(min: 1, minMessage: 'products must not be empty.')]
        #[Assert\Valid]
        public array $products,
        #[Assert\Email]
        public ?string $email_address = null,
        #[Assert\Type('string')]
        public ?string $phone = null,
        #[Assert\Type('string')]
        public ?string $city = null,
        #[Assert\Type('string')]
        public ?string $county = null,
        #[Assert\Type('string')]
        public ?string $address = null,
        #[Assert\Type('string')]
        public ?string $discount_code = null,
        #[Assert\Type('string')]
        public ?string $order_status = null,
    ) {
    }

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::trimStringFields(
            $data,
            ['email_address', 'phone', 'city', 'county', 'address', 'discount_code', 'order_status'],
        );
        $data = PayloadArrayNormalizer::coerceNumericStrings(
            $data,
            ['number'],
            ['discount_value', 'shipping', 'tax', 'total_value'],
        );
        $data['products'] = array_map(
            static fn(mixed $item): SaveOrderProductLine => SaveOrderProductLine::validateAndCreate(
                is_array($item) ? $item : throw new ValidationException('Each product must be an array.'),
            ),
            $data['products'] ?? [],
        );

        return parent::validateAndCreate($data);
    }

    #[Assert\Callback]
    public function validateContact(ExecutionContextInterface $context): void
    {
        if (self::isBlank($this->email_address) && self::isBlank($this->phone)) {
            $context->buildViolation('email_address or phone is required.')
                ->atPath('email_address')
                ->addViolation();
        }
    }

    /**
     * Optional fields are left out when empty (the platform treats `discount_code` as a coupon pattern),
     * except `email_address` and `phone`: the platform reads both keys without checking they exist,
     * so a phone-only or email-only order must still carry the empty one.
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $payload = $this->toArray();
        $optional = array_flip(['city', 'county', 'address', 'discount_code', 'order_status']);

        $payload['products'] = array_map(
            static fn(SaveOrderProductLine $line): array => $line->toApiPayload(),
            $this->products,
        );

        return array_diff_key($payload, $optional, ['email_address' => 1, 'phone' => 1])
            + ['email_address' => $this->email_address ?? '', 'phone' => $this->phone ?? '']
            + self::filterNonEmpty(array_intersect_key($payload, $optional));
    }

    private static function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
