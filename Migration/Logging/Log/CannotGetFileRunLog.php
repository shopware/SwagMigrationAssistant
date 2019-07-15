<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\LogType;

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
        return self::LOG_LEVEL_WARNING;
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
        return sprintf('%s.%s.title', $this->getSnippetRoot(), LogType::CANNOT_GET_FILE);
    }

    public function getDescriptionSnippet(): string
    {
        return sprintf('%s.%s.description', $this->getSnippetRoot(), LogType::CANNOT_GET_FILE);
    }
}
