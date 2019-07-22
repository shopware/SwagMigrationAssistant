<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class UnknownEntityLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $requiredForEntity;

    /**
     * @var string
     */
    private $requiredForSourceId;

    public function __construct(string $runId, string $entity, string $sourceId, string $requiredForEntity, string $requiredForSourceId)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->requiredForEntity = $requiredForEntity;
        $this->requiredForSourceId = $requiredForSourceId;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_%s_ENTITY_UNKNOWN', strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('Cannot find %s', $this->getEntity());
    }

    public function getParameters(): array
    {
        return [
            'requiredForEntity' => $this->requiredForEntity,
            'requiredForSourceId' => $this->requiredForSourceId,
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'The %s entity with the source id "%s" cannot find the depended %s entity with the source id "%s".',
            $args['requiredForEntity'],
            $args['requiredForSourceId'],
            $args['entity'],
            $args['sourceId']
        );
    }

    public function getTitleSnippet(): string
    {
        return sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_UNKNOWN');
    }

    public function getDescriptionSnippet(): string
    {
        return sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_UNKNOWN');
    }
}
