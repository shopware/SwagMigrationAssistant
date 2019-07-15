<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;
use SwagMigrationAssistant\Profile\Shopware\Logging\LogType;

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
        string $unserializedEntity,
        string $serializedData,
        ?string $entity = null,
        ?string $sourceId = null
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
        return LogType::INVALID_UNSERIALIZED_DATA;
    }

    public function getTitle(): string
    {
        return 'Invalid unserialized data';
    }

    public function getDescriptionArguments(): array
    {
        return [
            'translationEntity' => $this->unserializedEntity,
            'serializedData' => $this->serializedData,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf('The %s entity could not be converted cause of invalid unserialized object data.', $args['translationEntity']);
    }
}
