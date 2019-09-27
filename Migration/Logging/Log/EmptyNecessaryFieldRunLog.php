<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class EmptyNecessaryFieldRunLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $emptyField;

    public function __construct(string $runId, string $entity, string $sourceId, string $emptyField)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->emptyField = $emptyField;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_%s', strtoupper($this->getEntity()));
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getTitle(): string
    {
        return sprintf('The %s entity has one or more empty necessary fields', $this->getEntity());
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'emptyField' => $this->emptyField,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'The %s entity with the source id %s does not have the necessary data for the field(s): %s',
                $args['entity'],
                $args['sourceId'],
                $args['emptyField']
            );
    }

    public function getTitleSnippet(): string
    {
        return sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_EMPTY_NECESSARY_DATA_FIELDS');
    }

    public function getDescriptionSnippet(): string
    {
        return sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_EMPTY_NECESSARY_DATA_FIELDS');
    }
}
