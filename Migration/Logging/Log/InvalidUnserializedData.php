<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

class InvalidUnserializedData extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $unserializedEntity;

    /**
     * @var string
     */
    private $serializedData;

    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        string $unserializedEntity,
        string $serializedData
    ) {
        parent::__construct($runId, $entity, $sourceId);
        $this->unserializedEntity = $unserializedEntity;
        $this->serializedData = $serializedData;
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
