<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\AbstractGateway;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CategoryAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Exception\DatabaseConnectionException;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalAttributeReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCategoryReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCurrencyReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCustomerGroupReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCustomerReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalLanguageReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalMediaAlbumReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalMediaReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalNumberRangeReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalOrderReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalProductReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalPropertyGroupOptionReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalSalesChannelReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalTranslationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class Shopware55LocalGateway extends AbstractGateway
{
    public const GATEWAY_NAME = 'local';

    public function read(): array
    {
        $connection = $this->getConnection();
        /** @var Shopware55DataSet $dataSet */
        $dataSet = $this->migrationContext->getDataSet();

        switch ($dataSet::getEntity()) {
            case ProductDataSet::getEntity():
                $reader = new Shopware55LocalProductReader($connection, $this->migrationContext);

                return $reader->read();
            case CategoryDataSet::getEntity():
                $reader = new Shopware55LocalCategoryReader($connection, $this->migrationContext);

                return $reader->read();
            case CustomerGroupDataSet::getEntity():
                $reader = new Shopware55LocalCustomerGroupReader($connection, $this->migrationContext);

                return $reader->read();
            case CustomerDataSet::getEntity():
                $reader = new Shopware55LocalCustomerReader($connection, $this->migrationContext);

                return $reader->read();
            case OrderDataSet::getEntity():
                $reader = new Shopware55LocalOrderReader($connection, $this->migrationContext);

                return $reader->read();
            case MediaFolderDataSet::getEntity():
                $reader = new Shopware55LocalMediaAlbumReader($connection, $this->migrationContext);

                return $reader->read();
            case MediaDataSet::getEntity():
                $reader = new Shopware55LocalMediaReader($connection, $this->migrationContext);

                return $reader->read();
            case TranslationDataSet::getEntity():
                $reader = new Shopware55LocalTranslationReader($connection, $this->migrationContext);

                return $reader->read();
            case PropertyGroupOptionDataSet::getEntity():
                $reader = new Shopware55LocalPropertyGroupOptionReader($connection, $this->migrationContext);

                return $reader->read();
            case LanguageDataSet::getEntity():
                $reader = new Shopware55LocalLanguageReader($connection, $this->migrationContext);

                return $reader->read();
            case CurrencyDataSet::getEntity():
                $reader = new Shopware55LocalCurrencyReader($connection, $this->migrationContext);

                return $reader->read();
            case SalesChannelDataSet::getEntity():
                $reader = new Shopware55LocalSalesChannelReader($connection, $this->migrationContext);

                return $reader->read();
            case NumberRangeDataSet::getEntity():
                $reader = new Shopware55LocalNumberRangeReader($connection, $this->migrationContext);

                return $reader->read();
            case CategoryAttributeDataSet::getEntity():
            case CustomerAttributeDataSet::getEntity():
            case CustomerGroupAttributeDataSet::getEntity():
            case ManufacturerAttributeDataSet::getEntity():
            case OrderAttributeDataSet::getEntity():
            case ProductAttributeDataSet::getEntity():
            case ProductPriceAttributeDataSet::getEntity():
                $reader = new Shopware55LocalAttributeReader($connection, $this->migrationContext);

                return $reader->read($dataSet->getExtraQueryParameters());
            default:
                throw new Shopware55LocalReaderNotFoundException($dataSet::getEntity());
        }
    }

    public function readEnvironmentInformation(): EnvironmentInformation
    {
        $connection = $this->getConnection();

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
        $reader = new Shopware55LocalEnvironmentReader($connection, $this->migrationContext);
        $environmentData = $reader->read();

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

    private function getConnection(): Connection
    {
        $credentials = $this->migrationContext->getConnection()->getCredentialFields();

        $connectionParams = [
            'dbname' => $credentials['dbName'] ?? '',
            'user' => $credentials['dbUser'] ?? '',
            'password' => $credentials['dbPassword'] ?? '',
            'host' => $credentials['dbHost'] ?? '',
            'port' => $credentials['dbPort'] ?? '',
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        return DriverManager::getConnection($connectionParams);
    }
}
