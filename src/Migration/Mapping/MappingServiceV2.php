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
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MappingServiceV2 implements ResetInterface
{
    public const PLACEHOLDER = 'uuid-mapping-promise';

    /**
     * indexed by entityName + oldIdentifier
     * the value is always a list with at least one promise
     *
     * @var array<string, list<MappingPromise>>
     */
    private array $unfulfilledPromises = [];

    public function __construct(
        protected readonly Connection $connection,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Lookup of a mapping with optional creation parameters if it doesn't exist yet.
     *
     * Important: this method returns a reference to a string which is also internally stored.
     * It must be called like this:
     *
     * $data['newId'] = &$mappingService->getMapping('entity', 'oldId');
     *
     * The use of '&' is very important, otherwise it will only copy the placeholder string.
     * More info on this can be found in the PHP docs:
     * https://www.php.net/manual/en/language.references.return.php
     *
     * @param bool $shouldCreate if the mapping should be created if it doesn't exist yet
     * @param string|null $createWith if creating, use this Uuid to map to. All the same lookups will resolve to this
     *
     * @return string placeholder string which will be resolved later by calling @see MappingServiceV2->resolvePromises()
     */
    public function &getMapping(
        string $entityName,
        string $oldIdentifier,
        bool $shouldCreate = false,
        ?string $createWith = null,
    ): string {
        // todo: figure out a nice dev experience for debugging
        // todo: this is already a good start?
        $trace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $trace[0] ?? [];
        $callerString = ($caller['file'] ?? '') . '::' . ($caller['line'] ?? '');

        $ref = self::PLACEHOLDER . '(\'' . $entityName . '\', \'' . $oldIdentifier . '\') +++ ' . $callerString;

        $promise = new MappingPromise($ref, $entityName, $oldIdentifier, $shouldCreate, $createWith);

        $key = $entityName . $oldIdentifier;
        if (!isset($this->unfulfilledPromises[$key])) {
            $this->unfulfilledPromises[$key] = [];
        }
        $this->unfulfilledPromises[$key][] = $promise;

        return $ref;
    }

    public function resolvePromises(
        string $connectionId,
    ): void {
        if ($this->unfulfilledPromises === []) {
            return;
        }

        $mappingLookups = array_map(
            fn ($mappingPromises) => [
                'entity' => $mappingPromises[0]->entity,
                'oldIdentifier' => $mappingPromises[0]->sourceId,
            ],
            $this->unfulfilledPromises,
        );
        $dbMappings = $this->fetchDbMappings($connectionId, $mappingLookups);

        foreach ($dbMappings as $dbMapping) {
            $key = $dbMapping->getEntity() . $dbMapping->getOldIdentifier();
            $promises = $this->unfulfilledPromises[$key];
            foreach ($promises as $promise) {
                $promise->resolve($dbMapping->getEntityId());
            }
            unset($this->unfulfilledPromises[$key]);
        }

        // the remaining unfulfilled promises don't have a DB mapping yet
        $createDbMappings = [];
        foreach ($this->unfulfilledPromises as $promises) {
            // search if this particular lookup is allowed to create a mapping
            // and if exactly one createWith value was provided
            $shouldCreate = false;
            $createWith = null;
            foreach ($promises as $promise) {
                if ($promise->shouldCreate) {
                    $shouldCreate = true;
                }
                if ($promise->createWith) {
                    if ($createWith) {
                        // todo: proper error handling
                        throw new \RuntimeException('resolving mapping promise failed with multiple createWith values for ' . $promises[0]->sourceId . ' for ' . $promises[0]->entity);
                    }

                    $createWith = $promise->createWith;
                }
            }

            if (!$shouldCreate) {
                // todo: proper error handling
                throw new \RuntimeException('Could not resolve mapping promise ' . $promises[0]->sourceId . ' for ' . $promises[0]->entity);
            }

            // it's fine to create the corresponding mapping and resolve all promises with its value
            $newEntityId = $createWith ?? Uuid::randomHex();

            $dbMapping = new SwagMigrationMappingEntity();
            $dbMapping->setId(Uuid::randomHex());
            $dbMapping->setConnectionId($connectionId);
            $dbMapping->setEntity($promises[0]->entity);
            $dbMapping->setOldIdentifier($promises[0]->sourceId);
            $dbMapping->setEntityId($newEntityId);
            $dbMapping->setCreatedAt(new \DateTime());

            $createDbMappings[] = $dbMapping;

            // resolve promises
            // todo: should this only execute after successful DB insert?
            foreach ($promises as $promise) {
                $promise->resolve($newEntityId);
            }
        }

        unset($this->unfulfilledPromises);
        $this->unfulfilledPromises = [];

        $this->bulkCreateDbMappings($createDbMappings);
    }

    public function reset(): void
    {
        if ($this->unfulfilledPromises !== []) {
            $this->logger->error('Resetting MappingServiceV2 without resolving all promises');
        }

        unset($this->unfulfilledPromises);
        $this->unfulfilledPromises = [];
    }

    // todo: move SQL methods below into own class and try to clean them up further
    /**
     * @param list<array{entity: string, oldIdentifier: string}> $lookups
     *
     * @return list<SwagMigrationMappingEntity>
     */
    private function fetchDbMappings(string $connectionId, array $lookups): array
    {
        if ($lookups === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($lookups as $pair) {
            $placeholders[] = '(?, ?)';
            $params[] = $pair['entity'];
            $params[] = $pair['oldIdentifier'];
        }

        $sql = \sprintf(
            'SELECT id,
               connection_id AS connectionId,
               entity,
               old_identifier AS oldIdentifier,
               entity_id AS entityId,
               entity_value AS entityValue,
               checksum,
               additional_data AS additionalData
        FROM swag_migration_mapping
        WHERE connection_id = :connectionId AND (entity, old_identifier) IN (%s)',
            implode(', ', $placeholders)
        );

        $mappings = $this->connection->fetchAllAssociative(
            $sql,
            [
                'connectionId' => Uuid::fromHexToBytes($connectionId),
                ...$params,
            ],
            [
                'connectionId' => 'binary',
            ]
        );

        return array_map(
            function (array $mapping): SwagMigrationMappingEntity {
                $mapping['id'] = Uuid::fromBytesToHex($mapping['id']);
                $mapping['connectionId'] = Uuid::fromBytesToHex($mapping['connectionId']);
                $mapping['entityId'] = isset($mapping['entityId']) ? Uuid::fromBytesToHex($mapping['entityId']) : null;

                $entity = new SwagMigrationMappingEntity();
                $entity->assign($mapping);

                return $entity;
            },
            $mappings,
        );
    }

    /**
     * @param list<SwagMigrationMappingEntity> $mappings
     */
    private function bulkCreateDbMappings(array $mappings): void
    {
        if ($mappings === []) {
            return;
        }

        try {
            $isFirstInsert = true;
            $insertSql = 'INSERT INTO swag_migration_mapping (id, connection_id, entity, old_identifier, entity_id, entity_value, checksum, additional_data, created_at) VALUES ';
            $insertParams = [];
            $updateSql = ' ON DUPLICATE KEY
                       UPDATE entity = VALUES(entity),
                       old_identifier = VALUES(old_identifier),
                       entity_id = VALUES(entity_id),
                       entity_value = VALUES(entity_value),
                       checksum = VALUES(checksum),
                       additional_data = VALUES(additional_data),
                       updated_at = VALUES(created_at);';
            foreach ($mappings as $index => $writeMapping) {
                if ($isFirstInsert) {
                    $isFirstInsert = false;
                } else {
                    $insertSql .= ', ';
                }

                $insertSql .= \sprintf('(:id%d, :connectionId%d, :entity%d, :oldIdentifier%d, :entityId%d, :entityValue%d, :checksum%d, :additionalData%d, :createdAt%d)', $index, $index, $index, $index, $index, $index, $index, $index, $index);

                $insertParams['id' . $index] = Uuid::fromHexToBytes($writeMapping->getId());
                $insertParams['connectionId' . $index] = Uuid::fromHexToBytes($writeMapping->getConnectionId());
                $insertParams['entity' . $index] = $writeMapping->getEntity();
                $insertParams['oldIdentifier' . $index] = $writeMapping->getOldIdentifier();
                $insertParams['entityId' . $index] = $writeMapping->getEntityId() === null ? null : Uuid::fromHexToBytes($writeMapping->getEntityId());
                $insertParams['entityValue' . $index] = $writeMapping->getEntityValue();
                $insertParams['checksum' . $index] = $writeMapping->getChecksum();
                $insertParams['additionalData' . $index] = \json_encode($writeMapping->getAdditionalData());
                $insertParams['createdAt' . $index] = $writeMapping->getCreatedAt()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            }

            $this->connection->executeStatement($insertSql . $updateSql, $insertParams);
        } catch (\Exception) {
            $this->createDbMappingsIndividually($mappings);
        }
    }

    /**
     * @param list<SwagMigrationMappingEntity> $mappings
     */
    private function createDbMappingsIndividually(array $mappings): void
    {
        if ($mappings === []) {
            return;
        }

        foreach ($mappings as $writeMapping) {
            try {
                $insertSql = 'INSERT INTO swag_migration_mapping (id, connection_id, entity, old_identifier, entity_id, entity_value, checksum, additional_data, created_at)
                                VALUES (:id, :connectionId, :entity, :oldIdentifier, :entityId, :entityValue, :checksum, :additionalData, :createdAt)
                                ON DUPLICATE KEY
                       UPDATE entity = VALUES(entity),
                       old_identifier = VALUES(old_identifier),
                       entity_id = VALUES(entity_id),
                       entity_value = VALUES(entity_value),
                       checksum = VALUES(checksum),
                       additional_data = VALUES(additional_data),
                       updated_at = VALUES(created_at);';

                $insertParams = [];
                $insertParams['id'] = Uuid::fromHexToBytes($writeMapping->getId());
                $insertParams['connectionId'] = Uuid::fromHexToBytes($writeMapping->getConnectionId());
                $insertParams['entity'] = $writeMapping->getEntity();
                $insertParams['oldIdentifier'] = $writeMapping->getOldIdentifier();
                $insertParams['entityId'] = $writeMapping->getEntityId() === null ? null : Uuid::fromHexToBytes($writeMapping->getEntityId());
                $insertParams['entityValue'] = $writeMapping->getEntityValue();
                $insertParams['checksum'] = $writeMapping->getChecksum();
                $insertParams['additionalData'] = \json_encode($writeMapping->getAdditionalData());
                $insertParams['createdAt'] = $writeMapping->getCreatedAt()->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                $this->connection->executeStatement($insertSql, $insertParams);
            } catch (\Exception $e) {
                $this->logger->error(
                    'SwagMigrationAssistant: Error while writing migration mapping',
                    [
                        'error' => $e->getMessage(),
                        'mapping' => $writeMapping,
                    ]
                );
            }
        }
    }
}
