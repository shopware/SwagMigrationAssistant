<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Command;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Command\MigrateDataCommand;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\Premapping\PremappingReaderRegistry;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorService;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[Package('services-settings')]
class MigrateDataCommandTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationServicesTrait;

    private CommandTester $commandTester;

    private Command $command;

    /**
     * @var EntityRepository<SwagMigrationConnectionCollection>
     */
    private EntityRepository $connectionRepo;

    private string $connectionId = '';

    private Context $context;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private EntityRepository $runRepo;

    /**
     * @var EntityRepository<SwagMigrationDataCollection>
     */
    private EntityRepository $dataRepo;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->connectionRepo = static::getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->dataRepo = static::getContainer()->get('swag_migration_data.repository');

        $mappingService = static::getContainer()->get(MappingService::class);
        $loggingRepo = static::getContainer()->get('swag_migration_logging.repository');

        $kernel = self::getKernel();
        $application = new Application($kernel);
        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (): void {
            $this->connectionId = Uuid::randomHex();
            $this->connectionRepo->create(
                [
                    [
                        'id' => $this->connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $this->context
            );
        });

        $generalSettingsRepo = static::getContainer()->get('swag_migration_general_setting.repository');
        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function () use ($generalSettingsRepo): void {
            $generalSettingsRepo->create(
                [
                    [
                        'id' => Uuid::randomHex(),
                        'selectedConnectionId' => $this->connectionId,
                    ],
                ],
                $this->context
            );
        });

        $languageUuid = $this->getLanguageUuid(
            static::getContainer()->get('locale.repository'),
            static::getContainer()->get('language.repository'),
            'de-DE',
            $this->context
        );

        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CURRENCY, 'EUR', $this->context, null, [], Uuid::randomHex());
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'de-DE', $this->context, null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-GB', $this->context, null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-US', $this->context, null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'nl-NL', $this->context, null, [], $languageUuid);
        $mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'bn-IN', $this->context, null, [], $languageUuid);

        $dataFetcher = $this->getMigrationDataFetcher(
            static::getContainer()->get('swag_migration_logging.repository'),
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get(ReaderRegistry::class)
        );

        $setting = new GeneralSettingEntity();
        $setting->setSelectedConnectionId($this->connectionId);
        $setting->setUniqueIdentifier($this->connectionId);

        /** @var StaticEntityRepository<GeneralSettingCollection> $generalSettingsRepo */
        $generalSettingsRepo = new StaticEntityRepository([
            new GeneralSettingCollection([$setting]),
            new GeneralSettingDefinition(),
        ]);
        $application->add(new MigrateDataCommand(
            $generalSettingsRepo,
            static::getContainer()->get('swag_migration_connection.repository'),
            static::getContainer()->get('swag_migration_run.repository'),
            static::getContainer()->get(DataSetRegistry::class),
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $dataFetcher,
                new SwagMigrationAccessTokenService(
                    $this->runRepo
                ),
                new DataSelectionRegistry([
                    new ProductDataSelection(),
                    new CustomerAndOrderDataSelection(),
                ]),
                $this->dataRepo,
                static::getContainer()->get('swag_migration_media_file.repository'),
                static::getContainer()->get('sales_channel.repository'),
                static::getContainer()->get('theme.repository'),
                static::getContainer()->get(EntityIndexerRegistry::class),
                static::getContainer()->get(ThemeService::class),
                $mappingService,
                static::getContainer()->get('cache.object'),
                static::getContainer()->get(SwagMigrationDataDefinition::class),
                static::getContainer()->get(Connection::class),
                new LoggingService($loggingRepo),
                static::getContainer()->get(TrackingEventClient::class),
                static::getContainer()->get('messenger.bus.shopware')
            ),
            new PremappingService(
                new PremappingReaderRegistry([]),
                $mappingService,
                static::getContainer()->get('swag_migration_mapping.repository'),
                $this->runRepo,
                $this->connectionRepo
            ),
            $dataFetcher,
            $this->getMigrationDataConverter(
                static::getContainer()->get(EntityWriter::class),
                $mappingService,
                new DummyMediaFileService(),
                $loggingRepo,
                static::getContainer()->get(SwagMigrationDataDefinition::class),
                static::getContainer()->get('payment_method.repository'),
                static::getContainer()->get('shipping_method.repository'),
                static::getContainer()->get('country.repository'),
                static::getContainer()->get('sales_channel.repository')
            ),
            static::getContainer()->get(MigrationDataWriter::class),
            static::getContainer()->get(MediaFileProcessorService::class),
            static::getContainer()->get(MigrationContextFactory::class),
            'migration:migrate'
        ));
        $this->command = $application->find('migration:migrate');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecutionWithResume(): void
    {
        $dbConnection = static::getContainer()->get(Connection::class);

        $productTotalBefore = (int) $dbConnection->executeQuery('select count(*) from product')->fetchOne();
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'dataSelections' => ['products'],
        ]);
        $productTotalAfter = (int) $dbConnection->executeQuery('select count(*) from product')->fetchOne();
        static::assertSame(42, $productTotalAfter - $productTotalBefore);
    }
}
