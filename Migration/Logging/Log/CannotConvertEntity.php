<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class CannotConvertEntity extends BaseRunLogEntry
{
    public function __construct(string $runId, string $entity, ?string $sourceId = null)
    {
        parent::__construct($runId, $entity, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_CANNOT_CONVERT_%s', strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('The %s entity could not be converted', $this->getEntity());
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
            'The %s entity with the source id "%s" could not be converted.',
            $args['entity'],
            $args['sourceId'] ?? 'null'
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
