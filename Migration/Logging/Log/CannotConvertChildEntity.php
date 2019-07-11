<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

class CannotConvertChildEntity extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $parentEntity;

    /**
     * @var string
     */
    private $parentSourceId;

    public function __construct(string $runId, string $entity, string $parentEntity, string $parentSourceId)
    {
        parent::__construct($runId, $entity, null);
        $this->parentEntity = $parentEntity;
        $this->parentSourceId = $parentSourceId;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return sprintf('SWAG_MIGRATION_CANNOT_CONVERT_CHILD_%s_ENTITY', strtoupper($this->getEntity()));
    }

    public function getTitle(): string
    {
        return sprintf('The %s child entity could not be converted', $this->getEntity());
    }

    public function getDescriptionArguments(): array
    {
        return [
            'entity' => $this->getEntity(),
            'parentEntity' => $this->parentEntity,
            'parentSourceId' => $this->parentSourceId,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf(
            'The %s child entity from the %s parent entity with the id "%s" could not be converted.',
            $args['entity'],
            $args['parentEntity'],
            $args['parentSourceId']
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
