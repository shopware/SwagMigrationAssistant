<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class MigrationContextFactory implements MigrationContextFactoryInterface
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    public function __construct(
        ProfileRegistryInterface $profileRegistry,
        GatewayRegistryInterface $gatewayRegistry,
        DataSetRegistryInterface $dataSetRegistry
    ) {
        $this->profileRegistry = $profileRegistry;
        $this->gatewayRegistry = $gatewayRegistry;
        $this->dataSetRegistry = $dataSetRegistry;
    }

    public function create(
        SwagMigrationRunEntity $run,
        int $offset = 0,
        int $limit = 0,
        string $entity = ''
    ): ?MigrationContextInterface {
        $connection = $run->getConnection();
        if ($connection === null) {
            return null;
        }

        $profile = $this->profileRegistry->getProfile($connection->getProfileName());
        $migrationContext = new MigrationContext(
            $profile,
            $connection,
            $run->getId(),
            null,
            $offset,
            $limit
        );
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

        if ($entity !== '') {
            $dataSet = $this->dataSetRegistry->getDataSet($migrationContext, $entity);
            $migrationContext->setDataSet($dataSet);
        }

        return $migrationContext;
    }

    public function createByProfileName(string $profileName): MigrationContextInterface
    {
        $profile = $this->profileRegistry->getProfile($profileName);

        return new MigrationContext(
            $profile
        );
    }

    public function createByConnection(
        SwagMigrationConnectionEntity $connection
    ): MigrationContextInterface {
        $profile = $this->profileRegistry->getProfile(
            $connection->getProfileName()
        );
        $migrationContext = new MigrationContext(
            $profile,
            $connection
        );
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);
        $migrationContext->setGateway($gateway);

        return $migrationContext;
    }
}
