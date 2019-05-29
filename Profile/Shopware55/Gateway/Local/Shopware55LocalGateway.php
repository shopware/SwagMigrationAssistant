<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;
use SwagMigrationAssistant\Profile\Shopware55\Exception\DatabaseConnectionException;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Shopware55GatewayInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class Shopware55LocalGateway implements Shopware55GatewayInterface
{
    public const GATEWAY_NAME = 'local';

    /**
     * @var ReaderRegistry
     */
    private $readerRegistry;

    /**
     * @var ReaderInterface
     */
    private $localEnvironmentReader;

    /**
     * @var TableReaderInterface
     */
    private $localTableReader;

    public function __construct(
        ReaderRegistry $readerRegistry,
        ReaderInterface $localEnvironmentReader,
        TableReaderInterface $localTableReader
    ) {
        $this->readerRegistry = $readerRegistry;
        $this->localEnvironmentReader = $localEnvironmentReader;
        $this->localTableReader = $localTableReader;
    }

    public function supports(string $gatewayIdentifier): bool
    {
        return $gatewayIdentifier === Shopware55Profile::PROFILE_NAME . self::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        /** @var Shopware55DataSet $dataSet */
        $dataSet = $migrationContext->getDataSet();

        $reader = $this->readerRegistry->getReader($migrationContext);

        return $reader->read($migrationContext, $dataSet->getExtraQueryParameters());
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext): EnvironmentInformation
    {
        $connection = ConnectionFactory::createDatabaseConnection($migrationContext);

        try {
            $connection->connect();
        } catch (\Exception $e) {
            $error = new DatabaseConnectionException();

            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '-',
                [],
                [],
                '',
                'No warning.',
                $error->getErrorCode(),
                $error->getMessage()
            );
        }
        $connection->close();
        $environmentData = $this->localEnvironmentReader->read($migrationContext);

        $totals = [
            DefaultEntities::CATEGORY => $environmentData['categories'],
            DefaultEntities::PRODUCT => $environmentData['products'],
            DefaultEntities::CUSTOMER => $environmentData['customers'],
            DefaultEntities::ORDER => $environmentData['orders'],
            DefaultEntities::MEDIA => $environmentData['assets'],
            DefaultEntities::CUSTOMER_GROUP => $environmentData['customerGroups'],
            DefaultEntities::PROPERTY_GROUP_OPTION => $environmentData['configuratorOptions'],
            DefaultEntities::TRANSLATION => $environmentData['translations'],
            DefaultEntities::NUMBER_RANGE => $environmentData['numberRanges'],
            DefaultEntities::CURRENCY => $environmentData['currencies'],
        ];

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            Shopware55Profile::SOURCE_SYSTEM_VERSION,
            $environmentData['host'],
            $environmentData['structure'],
            $totals
        );
    }

    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        return $this->localTableReader->read($migrationContext, $tableName, $filter);
    }
}
