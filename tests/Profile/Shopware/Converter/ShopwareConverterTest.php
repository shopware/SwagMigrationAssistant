<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('after-sales')]
class ShopwareConverterTest extends TestCase
{
    private Context $context;

    private SwagMigrationConnectionEntity $connection;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setCredentialFields([]);
    }

    public function testConvertValueConvertsDateTimeWithMappedSourceTimezoneToUtcStorageFormat(): void
    {
        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->once())
            ->method('getValue')
            ->with($this->connection->getId(), 'source_timezone', 'timezone', $this->context)
            ->willReturn('Europe/Berlin');

        $converter = $this->createConverter($mappingService);

        [$converted, $source] = $converter->convertDateTimeValue('2026-05-01 12:30:00', $this->context);

        static::assertSame(['createdAt' => '2026-05-01 11:30:00.000'], $converted);
        static::assertSame([], $source);
    }

    public function testConvertValueCachesSourceTimezonePerConnection(): void
    {
        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->once())
            ->method('getValue')
            ->willReturn('Europe/Berlin');

        $converter = $this->createConverter($mappingService);

        [$firstConverted] = $converter->convertDateTimeValue('2026-05-01 12:00:00', $this->context);
        [$secondConverted] = $converter->convertDateTimeValue('2026-05-01 13:00:00', $this->context);

        static::assertSame('2026-05-01 11:00:00.000', $firstConverted['createdAt']);
        static::assertSame('2026-05-01 12:00:00.000', $secondConverted['createdAt']);
    }

    public function testConvertValueDoesNotConvertDateTimeWithoutContext(): void
    {
        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->never())->method('getValue');

        $converter = $this->createConverter($mappingService);

        [$converted, $source] = $converter->convertDateTimeValue('2026-05-01 12:30:00');

        static::assertSame([], $converted);
        static::assertSame(['createdAt' => '2026-05-01 12:30:00'], $source);
    }

    public function testConvertValueDoesNotConvertDateTimeWithInvalidMappedTimezone(): void
    {
        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->once())
            ->method('getValue')
            ->willReturn('Not/A_Timezone');

        $converter = $this->createConverter($mappingService);

        [$converted, $source] = $converter->convertDateTimeValue('2026-05-01 12:30:00', $this->context);

        static::assertSame([], $converted);
        static::assertSame(['createdAt' => '2026-05-01 12:30:00'], $source);
    }

    public function testConvertValueKeepsValidDateValueUnchanged(): void
    {
        $converter = $this->createConverter($this->createMock(MappingServiceInterface::class));

        [$converted, $source] = $converter->convertDateValue('2026-05-01');

        static::assertSame(['birthday' => '2026-05-01'], $converted);
        static::assertSame([], $source);
    }

    public function testConvertValueDoesNotConvertInvalidDateValue(): void
    {
        $converter = $this->createConverter($this->createMock(MappingServiceInterface::class));

        [$converted, $source] = $converter->convertDateValue('not a date');

        static::assertSame([], $converted);
        static::assertSame(['birthday' => 'not a date'], $source);
    }

    public function testGetAttributesConvertsDateTimeCustomFieldWithMappedSourceTimezone(): void
    {
        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->once())
            ->method('getMapping')
            ->with($this->connection->getId(), 'product_custom_field', 'release_time', $this->context)
            ->willReturn([
                'id' => Uuid::randomHex(),
                'additionalData' => [
                    'columnType' => 'datetime',
                ],
            ]);
        $mappingService->expects($this->once())
            ->method('getValue')
            ->with($this->connection->getId(), 'source_timezone', 'timezone', $this->context)
            ->willReturn('Europe/Berlin');

        $converter = $this->createConverter($mappingService);

        $converted = $converter->convertAttributes(
            ['release_time' => '2026-05-01 12:30:00'],
            'product',
            'shopware',
            $this->context
        );

        static::assertSame([
            'migration_shopware_product_release_time' => '2026-05-01 11:30:00.000',
        ], $converted);
    }

    private function createConverter(MappingServiceInterface $mappingService): TestShopwareConverter
    {
        $converter = new TestShopwareConverter(
            $mappingService,
            $this->createMock(LoggingServiceInterface::class)
        );
        $converter->setMigrationContext(new MigrationContext(
            $this->connection,
            new Shopware55Profile()
        ));

        return $converter;
    }
}
