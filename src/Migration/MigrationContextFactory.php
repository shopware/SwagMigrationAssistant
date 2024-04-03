<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
class MigrationContextFactory implements MigrationContextFactoryInterface
{
    public function __construct(
        private readonly ProfileRegistryInterface $profileRegistry,
        private readonly GatewayRegistryInterface $gatewayRegistry,
        private readonly DataSetRegistryInterface $dataSetRegistry
    ) {
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
