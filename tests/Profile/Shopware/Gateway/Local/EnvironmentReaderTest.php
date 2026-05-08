<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('fundamentals@after-sales')]
class EnvironmentReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private EnvironmentReader $environmentReader;

    private MigrationContext $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->environmentReader = new EnvironmentReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            $this->connection,
            new Shopware55Profile(),
            null,
            new CustomerDataSet(),
            $this->runId,
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        $data = $this->environmentReader->read($this->migrationContext);

        static::assertSame('de_DE', $data['defaultShopLanguage']);
        static::assertSame('sw55.local', $data['host']);
        static::assertArrayHasKey(0, $data['additionalData']);
        $additionalData = $data['additionalData'][0];
        static::assertSame('1', $additionalData['id']);
        static::assertSame('3', $additionalData['category_id']);
        static::assertSame('de_DE', $additionalData['locale']['locale']);
        static::assertSame('2', $additionalData['children'][0]['id']);
        static::assertSame('1', $additionalData['children'][0]['main_id']);
        static::assertSame('en_GB', $additionalData['children'][0]['locale']['locale']);
        static::assertSame('39', $additionalData['children'][0]['category_id']);
    }

    #[DataProvider('timezoneFixtureProvider')]
    public function testReadReadsTimezoneFromInstallationRootConfig(string $fixtureName, ?string $expectedTimezone): void
    {
        $credentialFields = $this->connection->getCredentialFields();
        static::assertIsArray($credentialFields);

        $credentialFields['installationRoot'] = __DIR__ . '/_fixtures/environment_reader/' . $fixtureName;
        $this->connection->setCredentialFields($credentialFields);

        $data = $this->environmentReader->read($this->migrationContext);

        static::assertSame($expectedTimezone, $data['timezone']);
    }

    public function testReadCachesEnvironmentInformationPerConnection(): void
    {
        $credentialFields = $this->connection->getCredentialFields();
        static::assertIsArray($credentialFields);

        $credentialFields['installationRoot'] = __DIR__ . '/_fixtures/environment_reader/valid';
        $this->connection->setCredentialFields($credentialFields);

        $firstData = $this->environmentReader->read($this->migrationContext);

        $credentialFields['installationRoot'] = __DIR__ . '/_fixtures/environment_reader/empty_database_timezone';
        $this->connection->setCredentialFields($credentialFields);

        $cachedData = $this->environmentReader->read($this->migrationContext);
        $secondMigrationContext = $this->createMigrationContextWithConnection(
            $this->createConnection($credentialFields)
        );
        $secondConnectionData = $this->environmentReader->read($secondMigrationContext);

        static::assertSame('Europe/Berlin', $firstData['timezone']);
        static::assertSame('Europe/Berlin', $cachedData['timezone']);
        static::assertNull($secondConnectionData['timezone']);
    }

    /**
     * @return array<string, array{fixtureName: string, expectedTimezone: string|null}>
     */
    public static function timezoneFixtureProvider(): array
    {
        return [
            'valid timezone' => [
                'fixtureName' => 'valid',
                'expectedTimezone' => 'Europe/Berlin',
            ],
            'config does not return array' => [
                'fixtureName' => 'config_does_not_return_array',
                'expectedTimezone' => null,
            ],
            'empty database timezone' => [
                'fixtureName' => 'empty_database_timezone',
                'expectedTimezone' => null,
            ],
            'missing database timezone' => [
                'fixtureName' => 'missing_database_timezone',
                'expectedTimezone' => null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $credentialFields
     */
    private function createConnection(array $credentialFields): SwagMigrationConnectionEntity
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setCredentialFields($credentialFields);
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        return $connection;
    }

    private function createMigrationContextWithConnection(SwagMigrationConnectionEntity $connection): MigrationContext
    {
        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            new CustomerDataSet(),
            $this->runId,
            0,
            10
        );

        $migrationContext->setGateway(new DummyLocalGateway());

        return $migrationContext;
    }
}
