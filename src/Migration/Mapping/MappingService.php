<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
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
     */
    public function __construct(
        protected EntityRepository $migrationMappingRepo,
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $mappingDefinition,
        protected Connection $connection,
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
        $cacheKey = $entityName . $oldIdentifier;
        if (isset($this->mappings[$cacheKey])) {
            return $this->mappings[$cacheKey];
        }

        $sql = 'SELECT id,
                       connection_id AS connectionId,
                       entity,
                       old_identifier AS oldIdentifier,
                       entity_uuid AS entityUuid,
                       entity_value AS entityValue,
                       checksum,
                       additional_data AS additionalData
                FROM swag_migration_mapping
                WHERE connection_id = :connectionId
                    AND entity = :entity
                    AND old_identifier = :oldIdentifier;';
        $mapping = $this->connection->fetchAssociative($sql, ['connectionId' => Uuid::fromHexToBytes($connectionId), 'entity' => $entityName, 'oldIdentifier' => $oldIdentifier]);
        if ($mapping === false) {
            return null;
        }

        $mapping['id'] = Uuid::fromBytesToHex($mapping['id']);
        $mapping['connectionId'] = Uuid::fromBytesToHex($mapping['connectionId']);
        $mapping['entityUuid'] = $mapping['entityUuid'] === null ? null : Uuid::fromBytesToHex($mapping['entityUuid']);
        if (!empty($mapping['additionalData'])) {
            $mapping['additionalData'] = \json_decode($mapping['additionalData'], true, 512, \JSON_THROW_ON_ERROR);
        } else {
            $mapping['additionalData'] = null;
        }

        // PHPStan does not recognize that fetchAssociative returns all required fields. We should move to a Mapping object.
        $mapping['oldIdentifier'] = $mapping['oldIdentifier'] === null ? null : (string) $mapping['oldIdentifier'];
        $mapping['entityValue'] = $mapping['entityValue'] === null ? null : (string) $mapping['entityValue'];
        $mapping['checksum'] = $mapping['checksum'] === null ? null : (string) $mapping['checksum'];

        $this->mappings[$cacheKey] = $mapping;

        return $mapping;
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
                $this->mappings[$entityName . $oldIdentifier] = [
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

    public function getValue(string $connectionId, string $entityName, string $oldIdentifier, Context $context): ?string
    {
        $cacheKey = $entityName . $oldIdentifier;
        if (isset($this->mappings[$cacheKey])) {
            return $this->mappings[$cacheKey]['entityValue'];
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
            $this->mappings[$cacheKey] = $mapping;

            return $value;
        }

        return null;
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

    public function writeMapping(): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        try {
            $isFirstInsert = true;
            $insertSql = 'INSERT INTO swag_migration_mapping (id, connection_id, entity, old_identifier, entity_uuid, entity_value, checksum, additional_data, created_at) VALUES ';
            $insertParams = [];
            $updateSql = ' ON DUPLICATE KEY
                       UPDATE entity = VALUES(entity),
                       old_identifier = VALUES(old_identifier),
                       entity_uuid = VALUES(entity_uuid),
                       entity_value = VALUES(entity_value),
                       checksum = VALUES(checksum),
                       additional_data = VALUES(additional_data),
                       updated_at = VALUES(created_at);';
            foreach ($this->writeArray as $index => $writeMapping) {
                if ($isFirstInsert) {
                    $isFirstInsert = false;
                } else {
                    $insertSql .= ', ';
                }

                $insertSql .= \sprintf('(:id%d, :connectionId%d, :entity%d, :oldIdentifier%d, :entityUuid%d, :entityValue%d, :checksum%d, :additionalData%d, :createdAt%d)', $index, $index, $index, $index, $index, $index, $index, $index, $index);

                $insertParams['id' . $index] = Uuid::fromHexToBytes($writeMapping['id']);
                $insertParams['connectionId' . $index] = Uuid::fromHexToBytes($writeMapping['connectionId']);
                $insertParams['entity' . $index] = $writeMapping['entity'];
                $insertParams['oldIdentifier' . $index] = $writeMapping['oldIdentifier'];
                $insertParams['entityUuid' . $index] = $writeMapping['entityUuid'] === null ? null : Uuid::fromHexToBytes($writeMapping['entityUuid']);
                $insertParams['entityValue' . $index] = $writeMapping['entityValue'];
                $insertParams['checksum' . $index] = $writeMapping['checksum'];
                $insertParams['additionalData' . $index] = \json_encode($writeMapping['additionalData']);
                $insertParams['createdAt' . $index] = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            }

            $this->connection->executeStatement($insertSql . $updateSql, $insertParams);
        } catch (\Exception) {
            $this->writePerEntry();
        } finally {
            $this->writeArray = [];
            // This should not really be necessary
            // but removing it could increase memory usage / needs profiling
            $this->mappings = [];
        }
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
        $this->mappings[$entity . $oldIdentifier] = $mapping;
        $this->writeArray[] = $mapping;
    }

    protected function saveListMapping(array $mapping): void
    {
        $entity = $mapping['entity'];
        $oldIdentifier = $mapping['oldIdentifier'];
        $this->mappings[$entity . $oldIdentifier][] = $mapping;
        $this->writeArray[] = $mapping;
    }

    private function writePerEntry(): void
    {
        foreach ($this->writeArray as $mapping) {
            try {
                $insertSql = 'INSERT INTO swag_migration_mapping (id, connection_id, entity, old_identifier, entity_uuid, entity_value, checksum, additional_data, created_at)
                                VALUES (:id, :connectionId, :entity, :oldIdentifier, :entityUuid, :entityValue, :checksum, :additionalData, :createdAt)
                                ON DUPLICATE KEY
                       UPDATE entity = VALUES(entity),
                       old_identifier = VALUES(old_identifier),
                       entity_uuid = VALUES(entity_uuid),
                       entity_value = VALUES(entity_value),
                       checksum = VALUES(checksum),
                       additional_data = VALUES(additional_data),
                       updated_at = VALUES(created_at);';

                $insertParams = [];
                $insertParams['id'] = Uuid::fromHexToBytes($mapping['id']);
                $insertParams['connectionId'] = Uuid::fromHexToBytes($mapping['connectionId']);
                $insertParams['entity'] = $mapping['entity'];
                $insertParams['oldIdentifier'] = $mapping['oldIdentifier'];
                $insertParams['entityUuid'] = $mapping['entityUuid'] === null ? null : Uuid::fromHexToBytes($mapping['entityUuid']);
                $insertParams['entityValue'] = $mapping['entityValue'];
                $insertParams['checksum'] = $mapping['checksum'];
                $insertParams['additionalData'] = \json_encode($mapping['additionalData']);
                $insertParams['createdAt'] = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                $this->connection->executeStatement($insertSql, $insertParams);
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
