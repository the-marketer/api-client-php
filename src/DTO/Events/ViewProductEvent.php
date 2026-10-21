<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Events;

use Symfony\Component\Validator\Constraints as Assert;
use TheMarketer\ApiClient\Common\AbstractPayload;

class ViewProductEvent extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $did,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $event,
        #[Assert\NotBlank]
        public int|string $product_id,
        #[Assert\NotBlank]
        #[Assert\Url]
        public string $url,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $http_user_agent,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $remote_addr,
        #[Assert\Type('string')]
        public ?string $source = null,
        #[Assert\Type('string')]
        public ?string $referrer = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        return array_merge(
            [
                'did' => $this->did,
                'event' => $this->event,
                'product_id' => $this->product_id,
                'url' => $this->url,
                'http_user_agent' => $this->http_user_agent,
                'remote_addr' => $this->remote_addr,
            ],
            self::filterNonEmpty(['source' => $this->source, 'referrer' => $this->referrer]),
        );
    }
}
