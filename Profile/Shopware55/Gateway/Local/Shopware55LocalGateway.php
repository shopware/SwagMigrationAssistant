<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCategoryReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalCustomerReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalEnvironmentReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalMediaReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalOrderReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalProductReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class Shopware55LocalGateway extends AbstractGateway
{
    public const GATEWAY_TYPE = 'local';

    public function read(): array
    {
        $connection = $this->getConnection();

        switch ($this->migrationContext->getEntity()) {
            case ProductDefinition::getEntityName():
                $reader = new Shopware55LocalProductReader($connection, $this->migrationContext);

                return $reader->read();
            case CategoryDefinition::getEntityName():
                $reader = new Shopware55LocalCategoryReader($connection, $this->migrationContext);

                return $reader->read();
            case CustomerDefinition::getEntityName():
                $reader = new Shopware55LocalCustomerReader($connection, $this->migrationContext);

                return $reader->read();
            case OrderDefinition::getEntityName():
                $reader = new Shopware55LocalOrderReader($connection, $this->migrationContext);

                return $reader->read();
            case MediaDefinition::getEntityName():
                $reader = new Shopware55LocalMediaReader($connection, $this->migrationContext);

                return $reader->read();
            default:
                throw new Shopware55LocalReaderNotFoundException($this->migrationContext->getEntity());
        }
    }

    public function readEnvironmentInformation(): EnvironmentInformation
    {
        $connection = $this->getConnection();

        try {
            $connection->connect();
        } catch (\Exception $e) {
            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '-',
                [],
                [],
                -1,
                'No warning.',
                404,
                'Database connection could not be established.'
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
            'translation' => $environmentData['translations'],
        ];

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            Shopware55Profile::SOURCE_SYSTEM_VERSION,
            $environmentData['host'],
            [],
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
