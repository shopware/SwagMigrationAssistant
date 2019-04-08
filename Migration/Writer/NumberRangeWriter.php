<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class NumberRangeWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $numberRangeRepo;

    public function __construct(EntityRepositoryInterface $numberRangeRepo)
    {
        $this->numberRangeRepo = $numberRangeRepo;
    }

    public function supports(): string
    {
        return DefaultEntities::NUMBER_RANGE;
    }

    public function writeData(array $data, Context $context): void
    {
        $this->numberRangeRepo->upsert($data, $context);
    }
}
