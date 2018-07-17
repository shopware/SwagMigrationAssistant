<?php declare(strict_types=1);


namespace SwagMigrationNext\Migration\Validator;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;

class ProductValidator implements ValidatorInterface
{
    /**
     * @var EntityRepository
     */
    private $entityRepository;

    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    public function supports(): string
    {
        return 'product';
    }

    public function validateData(array $data, Context $context): void
    {
        //Todo: Validate the data
    }
}