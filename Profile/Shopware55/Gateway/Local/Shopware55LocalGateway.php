<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\NumberRange\NumberRangeDefinition;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\MediaDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationNext\Profile\Shopware55\Exception\DatabaseConnectionException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalAttributeReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCategoryReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCurrencyReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCustomerGroupReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCustomerReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalEnvironmentReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalLanguageReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalMediaReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalNumberRangeReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalOrderReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalProductReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalPropertyGroupOptionReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalSalesChannelReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalTranslationReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

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
            CategoryDefinition::getEntityName() => $environmentData['categories'],
            ProductDefinition::getEntityName() => $environmentData['products'],
            CustomerDefinition::getEntityName() => $environmentData['customers'],
            OrderDefinition::getEntityName() => $environmentData['orders'],
            MediaDefinition::getEntityName() => $environmentData['assets'],
            CustomerGroupDefinition::getEntityName() => $environmentData['customerGroups'],
            PropertyGroupOptionDefinition::getEntityName() => $environmentData['configuratorOptions'],
            'translation' => $environmentData['translations'],
            NumberRangeDefinition::getEntityName() => $environmentData['numberRanges'],
            CurrencyDefinition::getEntityName() => $environmentData['currencies'],
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
