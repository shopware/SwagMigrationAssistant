<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Migration\Connection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\MigrationFingerprintService;
use SwagMigrationAssistant\Migration\Connection\MigrationConnectionFactory;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;

#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationConnectionFactory::class)]
class MigrationConnectionFactoryTest extends TestCase
{
    private MigrationContextFactory&MockObject $contextFactory;

    private MigrationDataFetcherInterface&MockObject $dataFetcher;

    private MigrationFingerprintService&MockObject $fingerprintService;

    /**
     * @var StaticEntityRepository<SwagMigrationConnectionCollection>
     */
    private StaticEntityRepository $connectionRepo;

    /**
     * @return iterable<string, array{
     *     connectionEntity: SwagMigrationConnectionEntity,
     *     mockedEnvironmentReturn: EnvironmentInformation,
     *     nameExists: bool,
     *     fingerprintDuplicate: bool,
     *     expectedException: ?\Exception}>
     */
    public static function validateProvider(): iterable
    {
        $successEnvironment = new EnvironmentInformation(
            'shopware5',
            '5.7.2',
            'http://shopware5.localhost',
            [],
            [],
            new RequestStatusStruct(),
        );

        $badSourceRequestStatus = new RequestStatusStruct('403', 'invalid credentials');
        $wrongCredentialsEnvironment = new EnvironmentInformation(
            'shopware5',
            '5.7.2',
            'http://shopware5.localhost',
            [],
            [],
            $badSourceRequestStatus,
        );

        $connectionEntity = new SwagMigrationConnectionEntity();
        $connectionEntity->setId('randomUuid');
        $connectionEntity->setName('test');
        $connectionEntity->setProfileName('profile');
        $connectionEntity->setGatewayName('gateway');
        $connectionEntity->setCredentialFields([
            'field1' => 'value1',
        ]);
        $connectionEntity->setSourceSystemFingerprint('fingerprint');

        yield 'success case' => [
            'connectionEntity' => $connectionEntity,
            'mockedEnvironmentReturn' => $successEnvironment,
            'nameExists' => false,
            'fingerprintDuplicate' => false,
            'expectedException' => null,
        ];

        yield 'existing name' => [
            'connectionEntity' => $connectionEntity,
            'mockedEnvironmentReturn' => $successEnvironment,
            'nameExists' => true,
            'fingerprintDuplicate' => false,
            'expectedException' => MigrationException::connectionNameNotUnique(),
        ];

        yield 'duplicate fingerprint' => [
            'connectionEntity' => $connectionEntity,
            'mockedEnvironmentReturn' => $successEnvironment,
            'nameExists' => false,
            'fingerprintDuplicate' => true,
            'expectedException' => MigrationException::duplicateSourceConnection(),
        ];

        yield 'wrong credentials' => [
            'connectionEntity' => $connectionEntity,
            'mockedEnvironmentReturn' => $wrongCredentialsEnvironment,
            'nameExists' => false,
            'fingerprintDuplicate' => false,
            'expectedException' => MigrationException::connectionValidationFailed(
                $badSourceRequestStatus->getCode(),
                $badSourceRequestStatus->getMessage(),
            ),
        ];
    }

    #[DataProvider('validateProvider')]
    public function testValidate(
        SwagMigrationConnectionEntity $connectionEntity,
        EnvironmentInformation $mockedEnvironmentReturn,
        bool $nameExists,
        bool $fingerprintDuplicate,
        ?\Exception $expectedException = null,
    ): void {
        $factory = $this->createFactory();

        if ($expectedException !== null) {
            $this->expectExceptionObject($expectedException);
        }

        if ($nameExists) {
            // search for the same name returns an existing connection
            $existingConnection = new SwagMigrationConnectionEntity();
            $existingConnection->setUniqueIdentifier('test');
            $this->connectionRepo->addSearch(
                new SwagMigrationConnectionCollection([
                    $existingConnection,
                ])
            );
        } else {
            // no existing connection with the same name found
            $this->connectionRepo->addSearch([]);
        }

        $this->dataFetcher
            ->method('getEnvironmentInformation')->willReturn($mockedEnvironmentReturn);

        if ($fingerprintDuplicate) {
            $this->fingerprintService->method('searchDuplicates')->willReturn(true);
        }

        $result = $factory->validate($connectionEntity, Context::createDefaultContext());

        static::assertSame($mockedEnvironmentReturn, $result);
    }

    public function testUpdate(): void
    {
        $factory = $this->createFactory();

        $connectionEntity = new SwagMigrationConnectionEntity();
        $connectionEntity->setId('randomUuid');
        $connectionEntity->setName('test');
        $connectionEntity->setProfileName('profile');
        $connectionEntity->setGatewayName('gateway');
        $connectionEntity->setCredentialFields([
            'field1' => 'value1',
        ]);
        $connectionEntity->setSourceSystemFingerprint('fingerprint');

        $factory->update($connectionEntity, Context::createDefaultContext());

        $actual = $this->connectionRepo->updates[0][0] ?? null;
        static::assertIsArray($actual, 'No update was performed');
        $expected = $connectionEntity->jsonSerialize();
        // ignore field order for comparison below
        ksort($actual);
        ksort($expected);

        static::assertSame(
            $expected,
            $actual,
            'Update content does not match'
        );
    }

    public function testPersistNew(): void
    {
        $factory = $this->createFactory();

        $connectionEntity = new SwagMigrationConnectionEntity();
        $connectionEntity->setId('randomUuid');
        $connectionEntity->setName('test');
        $connectionEntity->setProfileName('profile');
        $connectionEntity->setGatewayName('gateway');
        $connectionEntity->setCredentialFields([
            'field1' => 'value1',
        ]);
        $connectionEntity->setSourceSystemFingerprint('fingerprint');

        $factory->persistNew($connectionEntity, Context::createDefaultContext());

        $actual = $this->connectionRepo->creates[0][0] ?? null;
        static::assertIsArray($actual, 'No create was performed');
        $expected = $connectionEntity->jsonSerialize();
        // ignore field order for comparison below
        ksort($actual);
        ksort($expected);

        static::assertSame(
            $expected,
            $actual,
            'Create content does not match'
        );
    }

    private function createFactory(): MigrationConnectionFactory
    {
        $this->connectionRepo = new StaticEntityRepository(
            [],
            new SwagMigrationConnectionDefinition(),
        );
        $this->contextFactory = $this->createMock(MigrationContextFactory::class);
        $this->dataFetcher = $this->createMock(MigrationDataFetcherInterface::class);
        $this->fingerprintService = $this->createMock(MigrationFingerprintService::class);

        return new MigrationConnectionFactory(
            $this->connectionRepo,
            $this->contextFactory,
            $this->dataFetcher,
            $this->fingerprintService,
        );
    }
}
