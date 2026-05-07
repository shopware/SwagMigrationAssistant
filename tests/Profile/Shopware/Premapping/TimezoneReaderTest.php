<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Premapping;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TimezoneReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('after-sales')]
class TimezoneReaderTest extends TestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
    }

    public function testSupportsShopwareApiAndLocalGateways(): void
    {
        $reader = new TimezoneReader($this->createMock(GatewayRegistryInterface::class));

        static::assertTrue($reader->supports($this->createMigrationContext(ShopwareApiGateway::GATEWAY_NAME), []));
        static::assertTrue($reader->supports($this->createMigrationContext(ShopwareLocalGateway::GATEWAY_NAME), []));
    }

    public function testGetPremappingUsesDetectedSourceTimezone(): void
    {
        $migrationContext = $this->createMigrationContext(ShopwareLocalGateway::GATEWAY_NAME);
        $reader = $this->createReader($migrationContext, 'Europe/Berlin');
        $premapping = $reader->getPremapping($this->context, $migrationContext);

        static::assertSame(TimezoneReader::getMappingName(), $premapping->getEntity());
        static::assertNotEmpty($premapping->getChoices());
        static::assertCount(1, $premapping->getMapping());
        static::assertSame('timezone', $premapping->getMapping()[0]->getSourceId());
        static::assertSame('Europe/Berlin', $premapping->getMapping()[0]->getDescription());
        static::assertSame('Europe/Berlin', $premapping->getMapping()[0]->getDestinationUuid());
    }

    public function testGetPremappingKeepsConfiguredDestinationTimezone(): void
    {
        $connection = $this->createConnection(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setPremapping([
            new PremappingStruct(TimezoneReader::getMappingName(), [
                new PremappingEntityStruct('timezone', 'Europe/Berlin', 'America/New_York'),
            ]),
        ]);

        $migrationContext = $this->createMigrationContext(ShopwareLocalGateway::GATEWAY_NAME, $connection);
        $reader = $this->createReader($migrationContext, 'Europe/Berlin');
        $premapping = $reader->getPremapping($this->context, $migrationContext);

        static::assertSame('America/New_York', $premapping->getMapping()[0]->getDestinationUuid());
    }

    #[DataProvider('missingSourceTimezoneProvider')]
    public function testGetPremappingReturnsSelectableRowWhenSourceTimezoneCannotBeRead(?string $sourceTimezone): void
    {
        $migrationContext = $this->createMigrationContext(ShopwareLocalGateway::GATEWAY_NAME);
        $reader = $this->createReader($migrationContext, $sourceTimezone);
        $premapping = $reader->getPremapping($this->context, $migrationContext);

        static::assertNotEmpty($premapping->getChoices());
        static::assertCount(1, $premapping->getMapping());
        static::assertSame('timezone', $premapping->getMapping()[0]->getSourceId());
        static::assertSame('No source time zone', $premapping->getMapping()[0]->getDescription());
        static::assertSame('', $premapping->getMapping()[0]->getDestinationUuid());
    }

    public function testGetPremappingUsesEnvironmentInformationForApiGateway(): void
    {
        $migrationContext = $this->createMigrationContext(ShopwareApiGateway::GATEWAY_NAME);
        $reader = $this->createReader($migrationContext, 'UTC');
        $premapping = $reader->getPremapping($this->context, $migrationContext);

        static::assertSame('UTC', $premapping->getMapping()[0]->getDestinationUuid());
    }

    /**
     * @return array<string, array{sourceTimezone: string|null}>
     */
    public static function missingSourceTimezoneProvider(): array
    {
        return [
            'missing source timezone' => ['sourceTimezone' => null],
            'empty source timezone' => ['sourceTimezone' => ''],
            'invalid source timezone' => ['sourceTimezone' => 'Not/A_Timezone'],
        ];
    }

    private function createReader(MigrationContext $migrationContext, ?string $timezone): TimezoneReader
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $gateway->expects($this->once())
            ->method('readEnvironmentInformation')
            ->with($migrationContext, $this->context)
            ->willReturn($this->createEnvironmentInformation($timezone));

        $gatewayRegistry = $this->createMock(GatewayRegistryInterface::class);
        $gatewayRegistry->expects($this->once())
            ->method('getGateway')
            ->with($migrationContext)
            ->willReturn($gateway);

        return new TimezoneReader($gatewayRegistry);
    }

    private function createEnvironmentInformation(?string $timezone): EnvironmentInformation
    {
        return new EnvironmentInformation(
            'Shopware',
            '5.5.0',
            '',
            timezone: $timezone
        );
    }

    private function createMigrationContext(string $gatewayName, ?SwagMigrationConnectionEntity $connection = null): MigrationContext
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $gateway->method('getName')->willReturn($gatewayName);

        return new MigrationContext(
            $connection ?? $this->createConnection($gatewayName),
            new Shopware55Profile(),
            $gateway
        );
    }

    private function createConnection(string $gatewayName): SwagMigrationConnectionEntity
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName($gatewayName);
        $connection->setCredentialFields([]);

        return $connection;
    }
}
