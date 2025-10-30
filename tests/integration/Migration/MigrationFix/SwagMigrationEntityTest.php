<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\integration\Migration\MigrationFix;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationFix\SwagMigrationFixCollection;
use SwagMigrationAssistant\Migration\MigrationFix\SwagMigrationFixEntity;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;

class SwagMigrationEntityTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<SwagMigrationFixCollection>
     */
    private EntityRepository $migrationFixRepository;

    /**
     * @var EntityRepository<SwagMigrationConnectionCollection>
     */
    private EntityRepository $connectionRepository;

    private MappingService $mappingService;

    protected function setUp(): void
    {
        $this->migrationFixRepository = static::getContainer()->get('swag_migration_fix.repository');
        $this->connectionRepository = static::getContainer()->get('swag_migration_connection.repository');
        $this->mappingService = static::getContainer()->get(MappingService::class);
    }

    #[DataProvider('valueData')]
    public function testGetAndSetValue(mixed $value): void
    {
        $context = Context::createDefaultContext();
        $connectionId = $this->createConnection($context);
        $mappingId = $this->createMapping($connectionId);

        $fixId = Uuid::randomHex();

        $migrationFix = new SwagMigrationFixEntity();
        $migrationFix->setId($fixId);
        $migrationFix->setConnectionId($connectionId);
        $migrationFix->setMainMappingId($mappingId);
        $migrationFix->setPath('this.is.any.path');
        $migrationFix->setValue($value);

        $this->migrationFixRepository->upsert([$migrationFix->jsonSerialize()], $context);

        $criteria = new Criteria([$fixId]);

        $result = $this->migrationFixRepository->search($criteria, $context)->getEntities()->first();

        static::assertNotNull($result);
        static::assertSame($value, $result->getValue());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function valueData(): array
    {
        return [
            'integer' => ['value' => 12],
            'float' => ['value' => 42.12],
            'string' => ['value' => 'fooBar'],
            'array' => ['value' => ['key' => 'value']],
            'empty' => ['value' => ''],
            'null' => ['value' => null],
        ];
    }

    private function createMapping(string $connectionId): string
    {
        $mapping = $this->mappingService->createMapping(
            $connectionId,
            'any',
            'old_id_1',
            null,
            null,
            Uuid::randomHex(),
            'value'
        );

        $this->mappingService->writeMapping();

        return $mapping['id'];
    }

    private function createConnection(Context $context): string
    {
        $connectionId = Uuid::randomHex();

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionId): void {
            $this->connectionRepository->create(
                [
                    [
                        'id' => $connectionId,
                        'name' => 'connectionName',
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => 'profileName',
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );
        });

        return $connectionId;
    }
}
