<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;

class GeneralSettingRepo implements EntityRepositoryInterface
{
    /**
     * @var string
     */
    private $entityUuid;

    public function __construct(string $uuid)
    {
        $this->entityUuid = $uuid;
    }

    public function getDefinition(): EntityDefinition
    {
        return new GeneralSettingDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $setting = new GeneralSettingEntity();
        $setting->setSelectedConnectionId($this->entityUuid);
        $setting->setUniqueIdentifier($this->entityUuid);

        return new EntitySearchResult(1, new EntityCollection([$setting]), null, $criteria, $context);
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }
}
