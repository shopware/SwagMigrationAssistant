<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;

#[Package('services-settings')]
class MigrationContextFactory implements MigrationContextFactoryInterface
{
    /**
     * @param EntityRepository<GeneralSettingCollection> $generalSettingRepository
     * @param EntityRepository<SwagMigrationConnectionCollection> $migrationConnectionRepository
     */
    public function __construct(
        private readonly ProfileRegistryInterface $profileRegistry,
        private readonly GatewayRegistryInterface $gatewayRegistry,
        private readonly DataSetRegistryInterface $dataSetRegistry,
        private readonly EntityRepository $generalSettingRepository,
        private readonly EntityRepository $migrationConnectionRepository
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

    public function createBySelectedConnection(Context $context): MigrationContextInterface
    {
        $settings = $this->generalSettingRepository->search(new Criteria(), $context)->first();
        if (!$settings instanceof GeneralSettingEntity) {
            throw MigrationException::entityNotExists(GeneralSettingEntity::class, 'Default');
        }

        if ($settings->getSelectedConnectionId() === null) {
            throw MigrationException::noConnectionIsSelected();
        }

        $connection = $this->migrationConnectionRepository->search(new Criteria([$settings->getSelectedConnectionId()]), $context)->first();
        if (!$connection instanceof SwagMigrationConnectionEntity) {
            throw MigrationException::entityNotExists(SwagMigrationConnectionEntity::class, $settings->getSelectedConnectionId());
        }

        return $this->createByConnection($connection);
    }
}
