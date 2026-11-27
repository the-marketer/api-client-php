<?php

declare(strict_types=1);

namespace TheMarketer\ApiClient\DTO\Account;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use TheMarketer\ApiClient\Common\AbstractPayload;

/**
 * Body of `POST /allowed_domains` and `POST /allowed_domains/delete`, with the platform's rule
 * (AllowedDomainV1Request): `domain` is a string of at most 253 characters that reads as a valid host name.
 * It is sent as given; the platform stores {@see normalizeHost()} of it.
 */
class AllowedDomain extends AbstractPayload
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 253)]
        public string $domain,
    ) {}

    #[Assert\Callback]
    public function validateHost(ExecutionContextInterface $context): void
    {
        if (trim($this->domain) !== '' && self::normalizeHost($this->domain) === null) {
            $context->buildViolation('The domain must be a valid host name.')
                ->atPath('domain')
                ->addViolation();
        }
    }

    /**
     * The platform's normalisation (TechnicalIntegration::normalizeHost): lowercased host without scheme, userinfo,
     * port or path; `www.` is kept. Null when no valid host can be read (no dot, too long, invalid characters).
     */
    public static function normalizeHost(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (!is_string($host)) {
            return null;
        }

        $host = rtrim($host, '.');
        if (strlen($host) > 253 || !str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        return $host;
    }
}
