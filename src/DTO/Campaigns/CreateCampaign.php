<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Campaigns;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\Exception\ValidationException;

class CreateCampaign extends AbstractPayload
{
    /** @var array<string, class-string<AbstractPayload>> */
    private const NESTED = [
        'sender' => CreateCampaignSender::class,
        'audience' => CreateCampaignAudience::class,
        'subject' => CreateCampaignSubject::class,
        'content' => CreateCampaignContent::class,
        'scheduling' => CreateCampaignScheduling::class,
        'tracking' => CreateCampaignTracking::class,
    ];

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['sms', 'email', 'push'])]
        public string $type,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['ecommerce', 'regular', 'plaintext'])]
        public string $mode,
        #[Assert\Valid]
        public CreateCampaignSender $sender,
        #[Assert\Valid]
        public CreateCampaignAudience $audience,
        #[Assert\Valid]
        public CreateCampaignSubject $subject,
        #[Assert\Valid]
        public CreateCampaignContent $content,
        #[Assert\Valid]
        public CreateCampaignScheduling $scheduling,
        #[Assert\Valid]
        public CreateCampaignTracking $tracking,
    ) {}

    public static function validateAndCreate(array $data): static
    {
        foreach (self::NESTED as $key => $class) {
            $nested = $data[$key] ?? [];
            if (!is_array($nested)) {
                throw new ValidationException(sprintf('%s must be an array.', $key));
            }
            $data[$key] = $class::validateAndCreate($nested);
        }

        $instance = static::construct($data);
        $instance->validate();

        return $instance;
    }
}
