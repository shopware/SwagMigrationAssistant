<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\integration\Migration\Writer\MigrationFix;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationFix\SwagMigrationFixEntity;
use SwagMigrationAssistant\Migration\Writer\MigrationFix\MigrationFixApplier;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;

class MigrationFixApplierTest extends TestCase
{
    use IntegrationTestBehaviour;

    private MappingService $mappingService;

    protected function setUp(): void
    {
        $this->mappingService = new MappingService(
            $this->getContainer()->get('swag_migration_mapping.repository'),
            $this->getContainer()->get(EntityWriter::class),
            $this->getContainer()->get(SwagMigrationMappingDefinition::class),
            $this->getContainer()->get(Connection::class),
            new NullLogger()
        );
    }

    public function testApply(): void
    {
        $connectionId = Uuid::randomHex();
        $this->createConnection($connectionId);

        $idOne = Uuid::randomHex();
        $idTwo = Uuid::randomHex();
        $idThree = Uuid::randomHex();

        $fixApplier = new MigrationFixApplier($this->getContainer()->get(Connection::class));

        $mappingOne = $this->mappingService->createMapping(
            $connectionId,
            'any',
            'old_id_1',
            null,
            null,
            $idOne,
            'value'
        );

        $mappingTwo = $this->mappingService->createMapping(
            $connectionId,
            'any_other',
            'old_id_2',
            null,
            null,
            $idTwo,
            'any other value'
        );

        // create also mapping without fix
        $this->mappingService->createMapping(
            $connectionId,
            'any_other',
            'old_id_3',
            null,
            null,
            $idThree,
            'value three'
        );

        $this->mappingService->writeMapping();

        $this->createFix($mappingOne['id'], $connectionId, 'val1', 'first.path');
        $this->createFix($mappingOne['id'], $connectionId, ['nested' => ['array' => ['value' => 'nested array value']]], 'second.other.path');

        $this->createFix($mappingTwo['id'], $connectionId, 'val3', 'third.path');
        $this->createFix($mappingTwo['id'], $connectionId, 'val4', 'fourth.other.path');

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

        $fixApplier->apply($data, $connectionId);

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

    private function createFix(string $mappingId, string $connectionId, mixed $value, string $path): void
    {
        $migrationFix = new SwagMigrationFixEntity();
        $migrationFix->setId(Uuid::randomHex());
        $migrationFix->setConnectionId($connectionId);
        $migrationFix->setMainMappingId($mappingId);
        $migrationFix->setPath($path);
        $migrationFix->setValue($value);

        $this->getContainer()->get('swag_migration_fix.repository')->create([$migrationFix->jsonSerialize()], Context::createDefaultContext());
    }

    private function createConnection(string $connectionId): void
    {
        Context::createDefaultContext()->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionId): void {
            $this->getContainer()->get('swag_migration_connection.repository')->create(
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
        });
    }
}
