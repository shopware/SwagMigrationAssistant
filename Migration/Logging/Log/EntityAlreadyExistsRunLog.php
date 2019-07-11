<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class EntityAlreadyExistsRunLog extends BaseRunLogEntry
{
    public function __construct(string $runId, ?string $entity = null, ?string $sourceId = null)
    {
        parent::__construct($runId, $entity, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_%s_ENTITY_ALREADY_EXISTS', strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('The %s entity already exists', $this->getEntity());
    }

    public function getDescriptionArguments(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf(
            'The %s entity with source id "%s" already exists and cannot be written.',
            $args['entity'],
            $args['sourceId']
        );
    }

    public function getTitleSnippet(): string
    {
        return '...';
    }

    public function getDescriptionSnippet(): string
    {
        return '...';
    }
}
