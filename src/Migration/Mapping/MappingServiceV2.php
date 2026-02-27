<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Mapping;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class MappingServiceV2
{
    public const PLACEHOLDER = 'uuid-mapping-promise';

    /**
     * @var list<MappingPromise>
     */
    private array $unfulfilledPromises = [];

    public function __construct(
        protected readonly Connection $connection,
    ) {
    }

    public function &getMapping(
        string $entityName,
        string $oldIdentifier,
        bool $shouldCreate = false,
    ): string {
        $ref = self::PLACEHOLDER;

        $promise = new MappingPromise($ref, $entityName, $oldIdentifier, $shouldCreate);
        $this->unfulfilledPromises[] = $promise;

        return $ref;
    }

    public function resolvePromises(
        string $connectionId,
    ): void {
        $sql = '
        SELECT id,
               connection_id AS connectionId,
               entity,
               old_identifier AS oldIdentifier,
               entity_id AS entityId,
               entity_value AS entityValue,
               checksum,
               additional_data AS additionalData
        FROM swag_migration_mapping
        WHERE connection_id = :connectionId
            AND (entity, old_identifier) IN :mappingLookups;
        ';

        $mappingLookups = array_map(
            fn ($mappingPromise) => [$mappingPromise->entity, $mappingPromise->sourceId],
            $this->unfulfilledPromises,
        );

        $mappings = $this->connection->fetchAssociative(
            $sql,
            [
                'connectionId' => Uuid::fromHexToBytes($connectionId),
                'mappingLookups' => $mappingLookups,
            ],
            [
                'connectionId' => 'binary',
                'mappingLookups' => 'array',
            ]
        );

        // todo: iterate DB mappings and apply them

        // todo: also create new mappings if necessary based on the promises
    }
}
