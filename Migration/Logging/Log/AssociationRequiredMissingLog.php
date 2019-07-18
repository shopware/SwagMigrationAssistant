<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class AssociationRequiredMissingLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $requiredFor;

    public function __construct(string $runId, string $entity, string $sourceId, string $requiredFor)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->requiredFor = $requiredFor;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_%s', strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('Associated %s not found', $this->getEntity());
    }

    public function getParameters(): array
    {
        return [
            'missingEntity' => $this->getEntity(),
            'requiredFor' => $this->requiredFor,
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'The %s with the source id "%s" can not be found but is required for %s.',
            $args['missingEntity'],
            $args['sourceId'],
            $args['requiredFor']
        );
    }

    public function getTitleSnippet(): string
    {
        return sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING');
    }

    public function getDescriptionSnippet(): string
    {
        return sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING');
    }
}
