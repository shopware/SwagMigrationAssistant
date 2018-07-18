<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use SwagMigrationNext\Migration\Validator\ValidatorInterface;
use SwagMigrationNext\Migration\Validator\ValidatorRegistryInterface;

class MigrationValidateService implements MigrationValidateServiceInterface
{
    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var ValidatorInterface[]
     */
    private $validatorRegistry;

    public function __construct(
        EntityRepository $entityRepository,
        ValidatorRegistryInterface $validatorRegistry
    )
    {
        $this->entityRepository = $entityRepository;
        $this->validatorRegistry = $validatorRegistry;
    }

    public function validateData(MigrationContext $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entityType', $migrationContext->getEntityType()));
        $migration_data = $this->entityRepository->search($criteria, $context);

        $currentValidator = $this->validatorRegistry->getValidator($migrationContext->getEntityType());
        $currentValidator->validateData($migration_data->getElements(), $context);
    }
}