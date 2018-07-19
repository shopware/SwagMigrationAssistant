<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermsQuery;
use Shopware\Core\Framework\ORM\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Profile\ProfileRegistryInterface;

class MigrationCollectService implements MigrationCollectServiceInterface
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayFactoryRegistryInterface
     */
    private $gatewayFactoryRegistry;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    public function __construct(
        ProfileRegistryInterface $profileRegistry,
        GatewayFactoryRegistryInterface $gatewayFactoryRegistry,
        RepositoryInterface $migrationDataRepo
    ) {
        $this->profileRegistry = $profileRegistry;
        $this->gatewayFactoryRegistry = $gatewayFactoryRegistry;
        $this->migrationDataRepo = $migrationDataRepo;
    }

    public function fetchData(MigrationContext $migrationContext, Context $context): void
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getProfileName());
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        $requiredRelations = [];
        foreach (EntityRelationMapping::getMapping($migrationContext->getEntityName()) as $key => $entity) {
            $additionalRelationData = [];

            if ($key === 'main') {
                $criteria = new Criteria();
                $criteria->addFilter(new TermsQuery('entityName', $requiredRelations));
                $criteria->addSorting(new FieldSorting('entityName'));
                $result = $this->migrationDataRepo->search($criteria, $context)->getElements();

                /** @var ArrayStruct $item */
                foreach ($result as $item) {
                    $additionalRelationData[$item->get('entityName')][$item->get('oldIdentifier')] = $item->get('entityUuid');
                }
            }

            $profile->collectData($gateway, $entity, $context, $additionalRelationData);
            $requiredRelations[] = $entity;
        }
    }
}
