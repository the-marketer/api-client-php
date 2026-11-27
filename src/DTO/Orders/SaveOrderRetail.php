<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Orders;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Common\PayloadArrayNormalizer;

class SaveOrderRetail extends AbstractPayload
{
    /**
     * @param list<SaveOrderProductLine> $products
     */
    public function __construct(
        #[Assert\Positive]
        #[Assert\Type('integer')]
        public int $number,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email_address,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $phone,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $firstname,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $lastname,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $city,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $county,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $address,
        #[Assert\PositiveOrZero]
        #[Assert\Type('float')]
        public float $discount_value,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $discount_code,
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
        #[Assert\Positive]
        #[Assert\Type('integer')]
        public int $store_id,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $store_name,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $store_city,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $store_country,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        $data = PayloadArrayNormalizer::trimStringFields($data, ['email_address']);
        $data = PayloadArrayNormalizer::coerceNumericStrings(
            $data,
            ['number', 'store_id'],
            ['discount_value', 'shipping', 'tax', 'total_value'],
        );
        $data['products'] = array_map(
            static fn(array $item): SaveOrderProductLine => SaveOrderProductLine::validateAndCreate($item),
            $data['products'] ?? [],
        );

        return parent::validateAndCreate($data);
    }
}
