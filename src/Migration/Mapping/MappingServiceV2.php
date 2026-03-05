<?php declare(strict_types=1);

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

    public function &getMapping(
        string $entityName,
        string $oldIdentifier,
        bool $shouldCreate = false,
    ): string {
        $ref = self::PLACEHOLDER;

        $promise = new MappingPromise($ref, $entityName, $oldIdentifier, $shouldCreate);

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

        // todo: clean up the SQL query shenanigans
        $mappingLookups = array_map(
            fn ($mappingPromises) => [$mappingPromises[0]->entity, $mappingPromises[0]->sourceId],
            $this->unfulfilledPromises,
        );

        $placeholders = [];
        $params = [];
        $types = [];

        foreach ($mappingLookups as $pair) {
            $placeholders[] = '(?, ?)';
            $params[] = $pair[0];
            $types[] = 'string';
            $params[] = $pair[1];
            $types[] = 'string';
        }

        $sql = sprintf(
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


        $mappings = $this->connection->fetchAssociative(
            $sql,
            [
                'connectionId' => Uuid::fromHexToBytes($connectionId),
                ...$params,
            ],
            [
                'connectionId' => 'binary',
                ...$types,
            ]
        );

        if (is_array($mappings)) {
            foreach ($mappings as $mapping) {
                $key = $mapping['entity'] . $mapping['oldIdentifier'];
                $promises = $this->unfulfilledPromises[$key];
                foreach ($promises as $promise) {
                    $promise->resolve($mapping['entityId']);
                }
                unset($this->unfulfilledPromises[$key]);
            }
        }

        // the remaining unfulfilled promises don't have a DB mapping yet
        foreach ($this->unfulfilledPromises as $promises) {
            $shouldCreate = false;
            foreach ($promises as $promise) {
                if ($promise->shouldCreate) {
                    $shouldCreate = true;
                    break;
                }
            }

            if (!$shouldCreate) {
                // todo: proper error handling
                throw new \RuntimeException('Could not resolve mapping promise ' . $promises[0]->sourceId . ' for ' . $promises[0]->entity);
            }

            $newEntityId = Uuid::randomHex();
            $promise->resolve($newEntityId); // todo: should this only execute after the DB insert?

            $createMapping = [
                'id' => Uuid::randomHex(),
                'connection_id' => Uuid::fromHexToBytes($connectionId),
                'entity' => $promises[0]->entity,
                'old_identifier' => $promises[0]->sourceId,
                'entity_id' => Uuid::fromHexToBytes($newEntityId),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];
            // todo: bulk insert?
            $this->connection->insert('swag_migration_mapping', $createMapping, [
                'id' => 'binary',
                'connection_id' => 'binary',
                'entity_id' => 'binary',
            ]);
        }

        unset($this->unfulfilledPromises);
        $this->unfulfilledPromises = [];
    }

    public function reset(): void
    {
        if ($this->unfulfilledPromises !== []) {
            $this->logger->error('Resetting MappingServiceV2 without resolving all promises');
        }

        unset($this->unfulfilledPromises);
        $this->unfulfilledPromises = [];
    }
}
