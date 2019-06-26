<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;
use SwagMigrationAssistant\Profile\Shopware55\Exception\DatabaseConnectionException;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactoryInterface;
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

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(
        ReaderRegistry $readerRegistry,
        ReaderInterface $localEnvironmentReader,
        TableReaderInterface $localTableReader,
        ConnectionFactoryInterface $connectionFactory
    ) {
        $this->readerRegistry = $readerRegistry;
        $this->localEnvironmentReader = $localEnvironmentReader;
        $this->localTableReader = $localTableReader;
        $this->connectionFactory = $connectionFactory;
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
        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);

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
                new RequestStatusStruct($error->getErrorCode(), $error->getMessage())
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
            DefaultEntities::NEWSLETTER_RECIPIENT => $environmentData['newsletterRecipients'],
        ];

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            Shopware55Profile::SOURCE_SYSTEM_VERSION,
            $environmentData['host'],
            $totals,
            $environmentData['additionalData']
        );
    }

    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        return $this->localTableReader->read($migrationContext, $tableName, $filter);
    }
}
