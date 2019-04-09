<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Configuration\Aggregate\ConfigurationGroupOption\ConfigurationGroupOptionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class ConfigurationGroupOptionWriter implements WriterInterface
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
        return ConfigurationGroupOptionDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data) {
            $this->configuratorGroupOptionRepository->upsert($data, $context);
        });
    }
}
