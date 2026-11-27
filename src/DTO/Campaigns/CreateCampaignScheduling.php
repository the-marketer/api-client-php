<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Campaigns;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class CreateCampaignScheduling extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\DateTime(format: 'Y-m-d H:i')]
        public string $send_at,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [0, 1])]
        public int $use_optimal_time,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['opening', 'buying'])]
        public string $optimize_for,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        if (isset($data['use_optimal_time']) && is_string($data['use_optimal_time']) && is_numeric($data['use_optimal_time'])) {
            $data['use_optimal_time'] = (int) $data['use_optimal_time'];
        }

        $instance = static::construct($data);
        $instance->validate();

        return $instance;
    }
}
