<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class InvalidUnserializedData extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $unserializedEntity,
        private readonly string $serializedData
    ) {
        parent::__construct($runId, $entity, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE_INVALID_UNSERIALIZED_DATA';
    }

    public function getTitle(): string
    {
        return 'Invalid unserialized data';
    }

    /**
     * @return array{entity: ?string, sourceId: ?string, unserializedEntity: string, serializedData: string}
     */
    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'unserializedEntity' => $this->unserializedEntity,
            'serializedData' => $this->serializedData,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The %s entity with source id "%s" could not be converted because of invalid unserialized object data for the "%s" entity and the raw data is: %s',
            $args['entity'],
            $args['sourceId'],
            $args['unserializedEntity'],
            $args['serializedData']
        );
    }
}
