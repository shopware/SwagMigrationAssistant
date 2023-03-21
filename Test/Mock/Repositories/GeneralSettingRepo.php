<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;

class GeneralSettingRepo extends EntityRepository
{
    public function __construct(private readonly string $entityUuid)
    {
    }

    public function getDefinition(): EntityDefinition
    {
        return new GeneralSettingDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
        throw new \Error('GeneralSettingRepo->aggregate: Not implemented');
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        throw new \Error('GeneralSettingRepo->searchIds: Not implemented');
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $setting = new GeneralSettingEntity();
        $setting->setSelectedConnectionId($this->entityUuid);
        $setting->setUniqueIdentifier($this->entityUuid);

        return new EntitySearchResult(GeneralSettingDefinition::ENTITY_NAME, 1, new EntityCollection([$setting]), null, $criteria, $context);
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        throw new \Error('GeneralSettingRepo->update: Not implemented');
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
        throw new \Error('GeneralSettingRepo->upsert: Not implemented');
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
        throw new \Error('GeneralSettingRepo->create: Not implemented');
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
        throw new \Error('GeneralSettingRepo->delete: Not implemented');
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
        throw new \Error('GeneralSettingRepo->createVersion: Not implemented');
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null, ?CloneBehavior $behavior = null): EntityWrittenContainerEvent
    {
        throw new \Error('GeneralSettingRepo->clone: Not implemented');
    }
}
