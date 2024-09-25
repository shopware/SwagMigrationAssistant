<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Shopware\Core\System\Language\LanguageEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use Symfony\Contracts\Service\ResetInterface;

#[Package('services-settings')]
class MappingService implements MappingServiceInterface, ResetInterface
{
    protected array $migratedSalesChannels = [];

    protected array $writeArray = [];

    protected array $languageData = [];

    protected array $locales = [];

    protected array $mappings = [];

    protected LanguageEntity $defaultLanguageData;

    /**
     * @param EntityRepository<SwagMigrationMappingCollection> $migrationMappingRepo
     * @param EntityRepository<CountryStateCollection> $countryStateRepo
     */
    public function __construct(
        protected EntityRepository $migrationMappingRepo,
        protected EntityRepository $countryStateRepo,
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $mappingDefinition,
        protected LoggerInterface $logger,
    ) {
    }

    public function reset(): void
    {
        if (!empty($this->writeArray)) {
            $this->logger->error('SwagMigrationAssistant: Migration mapping was not empty on calling reset.');
        }

        $this->writeArray = [];
        $this->languageData = [];
        $this->locales = [];
        $this->mappings = [];
        $this->migratedSalesChannels = [];
    }

    public function getOrCreateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null,
        ?string $entityValue = null,
    ): array {
        $mapping = $this->getMapping($connectionId, $entityName, $oldIdentifier, $context);

        if (!isset($mapping)) {
            return $this->createMapping($connectionId, $entityName, $oldIdentifier, $checksum, $additionalData, $uuid, $entityValue);
        }

        if ($additionalData !== null) {
            $mapping['additionalData'] = $additionalData;
        }

        if ($uuid !== null) {
            $mapping['entityUuid'] = $uuid;
        }

        if ($entityValue !== null) {
            $mapping['entityValue'] = $entityValue;
        }

        if (
            $uuid !== null
            || $additionalData !== null
            || $entityValue !== null
        ) {
            $this->saveMapping($mapping);
        }

        return $mapping;
    }

    public function getMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
    ): ?array {
        if (isset($this->mappings[\md5($entityName . $oldIdentifier)])) {
            return $this->mappings[\md5($entityName . $oldIdentifier)];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldIdentifier));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                return null;
            }

            $mapping = [
                'id' => $element->getId(),
                'connectionId' => $element->getConnectionId(),
                'entity' => $element->getEntity(),
                'oldIdentifier' => $element->getOldIdentifier(),
                'entityUuid' => $element->getEntityUuid(),
                'entityValue' => $element->getEntityValue(),
                'checksum' => $element->getChecksum(),
                'additionalData' => $element->getAdditionalData(),
            ];
            $this->mappings[\md5($entityName . $oldIdentifier)] = $mapping;

            return $mapping;
        }

        return null;
    }

    public function createMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        ?string $checksum = null,
        ?array $additionalData = null,
        ?string $uuid = null,
        ?string $entityValue = null,
    ): array {
        $fallbackEntityUuid = $entityValue !== null ? null : Uuid::randomHex();

        $mapping = [
            'id' => Uuid::randomHex(),
            'connectionId' => $connectionId,
            'entity' => $entityName,
            'oldIdentifier' => $oldIdentifier,
            'entityUuid' => $uuid ?? $fallbackEntityUuid,
            'entityValue' => $entityValue,
            'checksum' => $checksum,
            'additionalData' => $additionalData,
        ];
        $this->saveMapping($mapping);

        return $mapping;
    }

    public function updateMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        array $updateData,
        Context $context,
    ): array {
        $mapping = $this->getMapping($connectionId, $entityName, $oldIdentifier, $context);

        if ($mapping === null) {
            return $this->createMapping(
                $connectionId,
                $entityName,
                $oldIdentifier,
                $updateData['checksum'] ?? null,
                $updateData['additionalData'] ?? null,
                $updateData['entityUuid'] ?? null
            );
        }

        $mapping = \array_merge($mapping, $updateData);
        $this->saveMapping($mapping);

        return $mapping;
    }

    public function getMappings(string $connectionId, string $entityName, array $ids, Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsAnyFilter('oldIdentifier', $ids));

        return $this->migrationMappingRepo->search($criteria, $context);
    }

    public function preloadMappings(array $mappingIds, Context $context): void
    {
        if (empty($mappingIds)) {
            return;
        }

        $result = $this->migrationMappingRepo->search(new Criteria($mappingIds), $context);

        if ($result->count() > 0) {
            $elements = $result->getEntities()->getElements();
            foreach ($elements as $mapping) {
                $entityName = $mapping->getEntity();
                $oldIdentifier = $mapping->getOldIdentifier();
                $this->mappings[\md5($entityName . $oldIdentifier)] = [
                    'id' => $mapping->getId(),
                    'connectionId' => $mapping->getConnectionId(),
                    'entity' => $entityName,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $mapping->getEntityUuid(),
                    'entityValue' => $mapping->getEntityValue(),
                    'checksum' => $mapping->getChecksum(),
                    'additionalData' => $mapping->getAdditionalData(),
                ];
            }
            unset($result);
        }
    }

    // TODO HERE?
    public function getUuidsByEntity(string $connectionId, string $entityName, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));

        $entities = $this->migrationMappingRepo->search($criteria, $context)->getEntities();

        $entityUuids = [];
        foreach ($entities as $entity) {
            $entityUuids[] = $entity->getEntityUuid();
        }

        return $entityUuids;
    }

    // TODO HERE?
    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string
    {
        if (isset($this->mappings[\md5($entityName . $oldIdentifier)])) {
            return $this->mappings[\md5($entityName . $oldIdentifier)]['entityValue'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldIdentifier));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $element = $result->getEntities()->first();

            if ($element === null) {
                return null;
            }

            $value = $element->getEntityValue();

            $mapping = [
                'id' => $element->getId(),
                'connectionId' => $element->getConnectionId(),
                'entity' => $element->getEntity(),
                'oldIdentifier' => $element->getOldIdentifier(),
                'entityUuid' => $element->getEntityUuid(),
                'entityValue' => $value,
                'checksum' => $element->getChecksum(),
                'additionalData' => $element->getAdditionalData(),
            ];
            $this->mappings[\md5($entityName . $oldIdentifier)] = $mapping;

            return $value;
        }

        return null;
    }

    public function createListItemMapping(
        string $connectionId,
        string $entityName,
        string $oldIdentifier,
        Context $context,
        ?array $additionalData = null,
        ?string $newUuid = null,
    ): void {
        $uuid = Uuid::randomHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;

            if ($this->isUuidDuplicate($connectionId, $entityName, $oldIdentifier, $newUuid, $context)) {
                return;
            }
        }

        $this->saveListMapping(
            [
                'id' => Uuid::randomHex(),
                'connectionId' => $connectionId,
                'entity' => $entityName,
                'oldIdentifier' => $oldIdentifier,
                'entityUuid' => $uuid,
                'entityValue' => null,
                'checksum' => null,
                'additionalData' => $additionalData,
            ]
        );
    }

    // TODO HERE?
    public function getUuidList(string $connectionId, string $entityName, string $identifier, Context $context): array
    {
        if (isset($this->mappings[\md5($entityName . $identifier)])) {
            return $this->mappings[\md5($entityName . $identifier)];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $identifier));

        $result = $this->migrationMappingRepo->search($criteria, $context);

        $uuidList = [];
        if ($result->getTotal() > 0) {
            foreach ($result->getEntities() as $entity) {
                $uuidList[] = $entity->getEntityUuid();
            }
        }

        $this->mappings[\md5($entityName . $identifier)] = $uuidList;

        return $uuidList;
    }

    public function deleteMapping(string $entityUuid, string $connectionId, Context $context): void
    {
        foreach ($this->writeArray as $key => $writeMapping) {
            if ($writeMapping['connectionId'] === $connectionId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($this->writeArray[$key]);
                $this->writeArray = \array_values($this->writeArray);

                break;
            }
        }

        foreach ($this->mappings as $hash => $mapping) {
            if (isset($mapping['entityUuid']) && $mapping['entityUuid'] === $entityUuid) {
                unset($this->mappings[$hash]);
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entityUuid', $entityUuid));
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->setLimit(1);

        $result = $this->migrationMappingRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $this->migrationMappingRepo->delete(\array_values($result->getData()), $context);
        }
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        try {
            $this->entityWriter->upsert(
                $this->mappingDefinition,
                $this->writeArray,
                WriteContext::createFromContext($context)
            );
        } catch (\Exception) {
            $this->writePerEntry($context);
        } finally {
            $this->writeArray = [];
            // This should not really be necessary
            // but removing it could increase memory usage / needs profiling
            $this->mappings = [];
        }
    }

    public function getCountryStateUuid(string $oldIdentifier, string $countryIso, string $countryStateCode, string $connectionId, Context $context): ?string
    {
        $countryStateMapping = $this->getMapping($connectionId, DefaultEntities::COUNTRY_STATE, $oldIdentifier, $context);

        if ($countryStateMapping !== null) {
            return $countryStateMapping['entityUuid'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', $countryIso . '-' . $countryStateCode));
        $criteria->addFilter(new EqualsFilter('country.iso', $countryIso));
        $criteria->setLimit(1);

        $countryStateUuid = $this->countryStateRepo->searchIds($criteria, $context)->firstId();

        if ($countryStateUuid !== null) {
            $this->saveMapping(
                [
                    'id' => Uuid::randomHex(),
                    'connectionId' => $connectionId,
                    'entity' => DefaultEntities::COUNTRY_STATE,
                    'oldIdentifier' => $oldIdentifier,
                    'entityUuid' => $countryStateUuid,
                ]
            );
        }

        return $countryStateUuid;
    }

    public function getMigratedSalesChannelUuids(string $connectionId, Context $context): array
    {
        if (isset($this->migratedSalesChannels[$connectionId])) {
            return $this->migratedSalesChannels[$connectionId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::SALES_CHANNEL));

        $result = $this->migrationMappingRepo->search($criteria, $context)->getEntities();

        $uuids = [];
        foreach ($result as $swagMigrationMappingEntity) {
            $uuid = $swagMigrationMappingEntity->getEntityUuid();

            if ($uuid === null) {
                continue;
            }

            $uuids[] = $uuid;
            $this->migratedSalesChannels[$connectionId][] = $uuid;
        }

        return $uuids;
    }

    protected function saveMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[\md5($entity . $oldIdentifier)] = $mapping;
        $this->writeArray[] = $mapping;
    }

    protected function saveListMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[\md5($entity . $oldIdentifier)][] = $mapping;
        $this->writeArray[] = $mapping;
    }

    private function isUuidDuplicate(string $connectionId, string $entityName, string $id, string $uuid, Context $context): bool
    {
        foreach ($this->writeArray as $item) {
            if (
                $item['connectionId'] === $connectionId
                && $item['entity'] === $entityName
                && $item['oldIdentifier'] === $id
                && $item['entityUuid'] === $uuid
            ) {
                return true;
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('connectionId', $connectionId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $id));
        $criteria->addFilter(new EqualsFilter('entityUuid', $uuid));

        $result = $this->migrationMappingRepo->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            return true;
        }

        return false;
    }

    private function writePerEntry(Context $context): void
    {
        foreach ($this->writeArray as $mapping) {
            try {
                $this->entityWriter->upsert(
                    $this->mappingDefinition,
                    [$mapping],
                    WriteContext::createFromContext($context)
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'SwagMigrationAssistant: Error while writing migration mapping',
                    [
                        'error' => $e->getMessage(),
                        'mapping' => $mapping,
                    ]
                );
            }
        }
    }
}
