<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

#[Package('services-settings')]
abstract class ShopwareConverter extends Converter
{
    /**
     * @psalm-suppress NonInvariantDocblockPropertyType
     *
     * @var Shopware6MappingServiceInterface
     */
    protected MappingServiceInterface $mappingService;

    protected Context $context;

    protected MigrationContextInterface $migrationContext;

    protected string $connectionId;

    protected string $runId;

    public function __construct(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->context = $context;
        $this->migrationContext = $migrationContext;

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $this->runId = $this->migrationContext->getRunUuid();

        $this->generateChecksum($data);
        $convertStructResult = $this->convertData($data);

        if (!empty($this->mainMapping)) {
            $this->updateMainMapping($this->migrationContext, $this->context);
        }

        return $convertStructResult;
    }

    /**
     * @param array<string, mixed> $data
     */
    abstract protected function convertData(array $data): ConvertStruct;

    protected function getMappingIdFacade(string $entityName, string $oldIdentifier): ?string
    {
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            $entityName,
            $oldIdentifier,
            $this->context
        );

        if (empty($mapping)) {
            return null;
        }

        $this->mappingIds[] = $mapping['id'];

        return $mapping['entityUuid'];
    }

    protected function getOrCreateMappingIdFacade(
        string $entityName,
        string $oldIdentifier,
        ?string $newIdentifier = null
    ): ?string {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            $entityName,
            $oldIdentifier,
            $this->context,
            null,
            null,
            $newIdentifier
        );

        $this->mappingIds[] = $mapping['id'];

        return $mapping['entityUuid'];
    }

    /**
     * @return array{id: string, connectionId: string, oldIdentifier: ?string, entityUuid: ?string, entityValue: ?string, checksum: ?string, additionalData: ?array<mixed>}
     */
    protected function getOrCreateMappingMainCompleteFacade(
        string $entityName,
        string $oldIdentifier,
        ?string $newIdentifier = null
    ): array {
        return $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            $entityName,
            $oldIdentifier,
            $this->context,
            $this->checksum,
            null,
            $newIdentifier
        );
    }

    /**
     * Replaces every id (associationIdKey) in the specified array of entities with the right mapping dependent one.
     *
     * @param array<string, mixed> $associationArray
     */
    protected function updateAssociationIds(array &$associationArray, string $entity, string $associationIdKey, string $sourceEntity, bool $logMissing = true, bool $unsetMissing = false): void
    {
        foreach ($associationArray as $key => $association) {
            if (!isset($association[$associationIdKey])) {
                continue;
            }

            $oldAssociationId = $association[$associationIdKey];

            $newAssociationId = $this->getMappingIdFacade(
                $entity,
                $oldAssociationId
            );

            if (empty($newAssociationId)) {
                if ($logMissing) {
                    $this->loggingService->addLogEntry(new AssociationRequiredMissingLog(
                        $this->runId,
                        $entity,
                        $oldAssociationId,
                        $sourceEntity
                    ));
                }

                if ($unsetMissing) {
                    unset($associationArray[$key][$associationIdKey]);
                }

                continue;
            }

            $associationArray[$key][$associationIdKey] = $newAssociationId;
        }
    }

    /**
     * Reformats the association ids array to a full association array (only containing the ids).
     * Example ($idsKey = 'optionIds', $associationKey = 'options'):
     * [
     *     "optionIds": [
     *         "bfaf0c7366e6454fb7516ab47435b01a"
     *     ],
     * ]
     * converted into:
     * [
     *     "options": [
     *         [
     *             "id" => "bfaf0c7366e6454fb7516ab47435b01a"
     *         ]
     *     ]
     * ]
     *
     * @param array<string, mixed> $converted
     */
    protected function reformatMtoNAssociation(array &$converted, string $idsKey, string $associationKey): void
    {
        $associationEntities = [];

        foreach ($converted[$idsKey] as $oldId) {
            $associationEntities[] = [
                'id' => $oldId,
            ];
        }

        $converted[$associationKey] = $associationEntities;
        unset($converted[$idsKey]);
    }

    /**
     * Removes all other keys except the ID (which may then be mapped), so no updates to the other entity occur.
     * Only supports single key entities.
     *
     * @param array<string, mixed> $converted
     */
    protected function updateEntityAssociation(array &$converted, string $entityKey, ?string $mappingEntity = null): void
    {
        $associationEntities = [];

        if (!isset($converted[$entityKey])) {
            return;
        }

        foreach ($converted[$entityKey] as $associatedEntity) {
            $identifier = $associatedEntity['id'];

            if ($mappingEntity !== null) {
                $identifier = $this->getMappingIdFacade($mappingEntity, $identifier);
            }

            $associationEntities[] = [
                'id' => $identifier,
            ];
        }

        $converted[$entityKey] = $associationEntities;
    }
}
