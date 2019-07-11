<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class CannotGetFileRunLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $uri;

    public function __construct(string $runId, string $entity, string $sourceId, string $uri)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->uri = $uri;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_CANNOT_GET_%s_FILE', strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('The %s file cannot be downloaded / copied', $this->getEntity());
    }

    public function getDescriptionArguments(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'uri' => $this->uri,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf(
            'The %s file with the uri "%s" and media id "%s" cannot be downloaded / copied.',
            $args['entity'],
            $args['uri'],
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
