<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\integration\Migration\ErrorResolution;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\ErrorResolution\Entity\SwagMigrationFixEntity;
use SwagMigrationAssistant\Migration\ErrorResolution\ErrorResolutionService;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class ErrorResolutionServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testApply(): void
    {
        $connectionId = Uuid::randomHex();
        $connection = $this->createConnection($connectionId);
        $run = $this->createRun($connection);

        $idOne = Uuid::randomHex();
        $idTwo = Uuid::randomHex();
        $idThree = Uuid::randomHex();

        $service = new ErrorResolutionService($this->getContainer()->get(Connection::class));

        $this->createFixAndLogging($connection->getId(), $idOne, 'val1', 'first.path', $run);
        $this->createFixAndLogging($connection->getId(), $idOne, ['nested' => ['array' => ['value' => 'nested array value']]], 'second.other.path', $run);

        $this->createFixAndLogging($connection->getId(), $idTwo, 'val3', 'third.path', $run);
        $this->createFixAndLogging($connection->getId(), $idTwo, 'val4', 'fourth.other.path', $run);

        $data = [
            [
                'id' => $idOne,
                'first' => ['UntouchedKey' => 'UntouchedValue'],
                'second' => [
                    'UntouchedKey' => 'UntouchedValue',
                    'other' => ['UntouchedKey' => 'UntouchedValue'],
                ],
            ],
            ['id' => $idTwo, 'third' => ['path' => 'oldValueShouldNotExistAfterApply']],
            ['id' => $idThree],
        ];

        $service->apply($data, $connection->getId(), $run->getId());

        $expected = [[
            'id' => $idOne,
            'first' => ['UntouchedKey' => 'UntouchedValue', 'path' => 'val1'],
            'second' => [
                'UntouchedKey' => 'UntouchedValue',
                'other' => [
                    'UntouchedKey' => 'UntouchedValue',
                    'path' => [
                        'nested' => [
                            'array' => [
                                'value' => 'nested array value',
                            ],
                        ],
                    ],
                ],
            ],
        ], [
            'id' => $idTwo,
            'third' => ['path' => 'val3'],
            'fourth' => ['other' => ['path' => 'val4']],
        ], [
            'id' => $idThree,
        ]];

        static::assertSame($expected, $data);
    }

    private function createFixAndLogging(string $connectionId, string $entityId, mixed $value, string $path, SwagMigrationRunEntity $swagMigrationRunEntity): void
    {
        $context = Context::createDefaultContext();
        $migrationFix = new SwagMigrationFixEntity();
        $migrationFix->setId(Uuid::randomHex());
        $migrationFix->setConnectionId($connectionId);
        $migrationFix->setEntityId($entityId);
        $migrationFix->setPath($path);
        $migrationFix->setValue($value);
        $this->getContainer()->get('swag_migration_fix.repository')->create([\json_decode(\json_encode($migrationFix, \JSON_THROW_ON_ERROR), true)], $context);

        $loggingEntity = new SwagMigrationLoggingEntity();
        $loggingEntity->setId(Uuid::randomHex());
        $loggingEntity->setEntityId($entityId);
        $loggingEntity->setLevel('level');
        $loggingEntity->setCode('code');
        $loggingEntity->setRunId($swagMigrationRunEntity->getId());
        $loggingEntity->setRun($swagMigrationRunEntity);
        $loggingEntity->setProfileName('profileName');
        $loggingEntity->setGatewayName('gatewayName');
        $loggingEntity->setUserFixable(true);
        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($loggingEntity): void {
            $this->getContainer()->get('swag_migration_logging.repository')->create([\json_decode(\json_encode($loggingEntity, \JSON_THROW_ON_ERROR), true)], $context);
        });
    }

    private function createRun(SwagMigrationConnectionEntity $connection): SwagMigrationRunEntity
    {
        $swagMigrationRun = null;
        Context::createDefaultContext()->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use (&$swagMigrationRun, $connection): void {
            $runRepository = $this->getContainer()->get('swag_migration_run.repository');
            $runId = Uuid::randomHex();
            $runData = [
                [
                    'id' => $runId,
                    'connection_id' => $connection->getId(),
                    'connection' => $connection->jsonSerialize(),
                    'step' => 'apply-fixes',
                ],
            ];

            $runRepository->create($runData, $context);
            $criteria = new Criteria([$runId]);
            $swagMigrationRun = $runRepository->search($criteria, $context)->first();
        });

        static::assertInstanceOf(SwagMigrationRunEntity::class, $swagMigrationRun);

        return $swagMigrationRun;
    }

    private function createConnection(string $connectionId): SwagMigrationConnectionEntity
    {
        $connection = null;
        Context::createDefaultContext()->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use (&$connection, $connectionId): void {
            $connectionRepository = $this->getContainer()->get('swag_migration_connection.repository');

            $connectionRepository->create(
                [
                    [
                        'id' => $connectionId,
                        'name' => 'AnyTestConnection',
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => 'TestProfileName',
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );

            $criteria = new Criteria([$connectionId]);
            $connection = $connectionRepository->search($criteria, $context)->first();
        });

        static::assertInstanceOf(SwagMigrationConnectionEntity::class, $connection);

        return $connection;
    }
}
