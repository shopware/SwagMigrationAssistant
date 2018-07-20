<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\System\Tax\TaxDefinition;

class TaxWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface
     */
    private $taxRepository;

    public function __construct(RepositoryInterface $taxRepository)
    {
        $this->taxRepository = $taxRepository;
    }

    public function supports(): string
    {
        return TaxDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->taxRepository->upsert($data, $context);
    }
}
