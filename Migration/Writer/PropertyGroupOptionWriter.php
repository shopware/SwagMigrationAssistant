<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class PropertyGroupOptionWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $configuratorGroupOptionRepository;

    public function __construct(EntityRepositoryInterface $configuratorGroupOptionRepository)
    {
        $this->configuratorGroupOptionRepository = $configuratorGroupOptionRepository;
    }

    public function supports(): string
    {
        return PropertyGroupOptionDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data) {
            $this->configuratorGroupOptionRepository->upsert($data, $context);
        });
    }
}
