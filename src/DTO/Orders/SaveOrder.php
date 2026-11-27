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
     * @param string|null                $currency     the order's ISO 4217 code, 3 uppercase letters (`RON`, `EUR`)
     * @param float|null                 $refund_value the amount refunded so far, >= 0
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
        public int|string|null $store_id = null,
        #[Assert\Type('string')]
        public ?string $store_name = null,
        #[Assert\Type('string')]
        public ?string $store_city = null,
        #[Assert\Type('string')]
        public ?string $store_country = null,
        #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'currency must be a 3-letter uppercase ISO 4217 code.')]
        public ?string $currency = null,
        #[Assert\PositiveOrZero]
        public ?float $refund_value = null,
    ) {
    }

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::trimStringFields(
            $data,
            [
                'email_address', 'phone', 'city', 'county', 'address', 'discount_code', 'order_status',
                'store_name', 'store_city', 'store_country', 'currency',
            ],
        );
        // `number` is sent as given: the platform compares it as text, so `"007"` must not become `7`.
        $data = PayloadArrayNormalizer::coerceNumericStrings(
            $data,
            [],
            ['discount_value', 'shipping', 'tax', 'total_value', 'refund_value'],
        );
        if (($data['currency'] ?? null) === '') {
            $data['currency'] = null;
        }
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
     * Optional fields (including the physical store, `store_*`, the order's `currency` and `refund_value`) are left out when empty (the platform treats `discount_code` as a coupon pattern),
     * except `email_address` and `phone`: the platform reads both keys without checking they exist,
     * so a phone-only or email-only order must still carry the empty one.
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        $payload = $this->toArray();
        $optional = array_flip([
            'city', 'county', 'address', 'discount_code', 'order_status',
            'store_id', 'store_name', 'store_city', 'store_country', 'currency', 'refund_value',
        ]);

        // A list, even when the caller's keys have gaps (a line dropped before sending).
        $payload['products'] = array_values(array_map(
            static fn(SaveOrderProductLine $line): array => $line->toApiPayload(),
            $this->products,
        ));

        return array_diff_key($payload, $optional, ['email_address' => 1, 'phone' => 1])
            + ['email_address' => $this->email_address ?? '', 'phone' => $this->phone ?? '']
            + self::filterNonEmpty(array_intersect_key($payload, $optional));
    }

    private static function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
