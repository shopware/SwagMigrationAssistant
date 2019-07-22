<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class DataSetNotFoundLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $profileName;

    public function __construct(string $runUuid, string $entity, string $sourceId, string $profileName)
    {
        parent::__construct($runUuid, $entity, $sourceId);
        $this->profileName = $profileName;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__DATASET_NOT_FOUND';
    }

    public function getTitle(): string
    {
        return 'DataSet not found';
    }

    public function getParameters(): array
    {
        return [
            'profileName' => $this->profileName,
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'DataSet for profile "%s" and entity "%s" not found. Entity with id "%s" could not be processed.',
            $args['profileName'],
            $args['entity'],
            $args['sourceId']
        );
    }
}
