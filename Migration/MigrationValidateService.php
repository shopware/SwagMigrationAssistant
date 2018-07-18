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
    private $migrationDataRepository;

    /**
     * @var ValidatorInterface[]
     */
    private $validatorRegistry;

    public function __construct(
        EntityRepository $migrationDataRepository,
        ValidatorRegistryInterface $validatorRegistry
    ) {
        $this->migrationDataRepository = $migrationDataRepository;
        $this->validatorRegistry = $validatorRegistry;
    }

    public function validateData(MigrationContext $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entityName', $migrationContext->getEntityName()));
        $migrationData = $this->migrationDataRepository->search($criteria, $context);

        $currentValidator = $this->validatorRegistry->getValidator($migrationContext->getEntityName());
        $currentValidator->validateData($migrationData->getElements(), $context);
    }
}
