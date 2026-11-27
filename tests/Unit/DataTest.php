<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use TheMarketer\ApiClient\DTO\Campaigns\CampaignId;
use TheMarketer\ApiClient\DTO\Campaigns\CreateCampaignScheduling;
use TheMarketer\ApiClient\DTO\Credentials\CheckCredentials;
use TheMarketer\ApiClient\DTO\Orders\SaveOrderProductLine;
use TheMarketer\ApiClient\Exception\ValidationException;

/**
 * Verifică comportamentul de bază al {@see \TheMarketer\ApiClient\Common\Data} prin DTO-uri concrete.
 */
final class DataTest extends PHPUnitTestCase
{
    public function testValidateAndCreateRejectsUnknownKeysNamingThem(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unknown field(s): extra, other');

        CampaignId::validateAndCreate(['id' => '42', 'extra' => 'x', 'other' => 'y']);
    }

    public function testOverriddenValidateAndCreateAlsoRejectsUnknownKeys(): void
    {
        // CreateCampaignScheduling builds through Data::construct() in its own validateAndCreate().
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unknown field(s): typo');

        CreateCampaignScheduling::validateAndCreate([
            'send_at' => '2025-06-01 12:00',
            'use_optimal_time' => 0,
            'typo' => 1,
        ]);
    }

    public function testNestedDtoRejectsUnknownKeys(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('unknown field(s): colour');

        SaveOrderProductLine::validateAndCreate(['product_id' => 1, 'price' => 1.0, 'quantity' => 1, 'colour' => 'red']);
    }

    public function testValidateAndCreateAcceptsKnownKeys(): void
    {
        $dto = CampaignId::validateAndCreate(['id' => '42']);

        $this->assertSame('42', $dto->id);
    }

    public function testToArrayIncludesScalarProperties(): void
    {
        $dto = CheckCredentials::validateAndCreate(['k' => 'a', 'r' => 'b', 'u' => 'c']);

        $this->assertSame(['k' => 'a', 'r' => 'b', 'u' => 'c'], $dto->toArray());
    }
}
