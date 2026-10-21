<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Events;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;
use TheMarketer\ApiClient\DTO\Orders\SaveOrder;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * The `__sm__order` tracking event: the same order fields as {@see SaveOrder}, plus the tracking fields.
 */
class OrderEvent extends AbstractPayload
{
    private const TRACKING_FIELDS = ['did', 'event', 'url', 'http_user_agent', 'remote_addr', 'source', 'referrer'];

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $did,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $event,
        #[Assert\NotBlank]
        #[Assert\Url]
        public string $url,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $http_user_agent,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $remote_addr,
        #[Assert\Valid]
        public SaveOrder $order,
        #[Assert\Type('string')]
        public ?string $source = null,
        #[Assert\Type('string')]
        public ?string $referrer = null,
    ) {
    }

    public static function validateAndCreate(array $data): static
    {
        $tracking = array_intersect_key($data, array_flip(self::TRACKING_FIELDS));
        $tracking['order'] = SaveOrder::validateAndCreate(array_diff_key($data, $tracking));

        return parent::validateAndCreate($tracking);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        return $this->order->toApiPayload() + [
            'did' => $this->did,
            'event' => $this->event,
            'url' => $this->url,
            'http_user_agent' => $this->http_user_agent,
            'remote_addr' => $this->remote_addr,
        ] + self::filterNonEmpty(['source' => $this->source, 'referrer' => $this->referrer]);
    }
}
