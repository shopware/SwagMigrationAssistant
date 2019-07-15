<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\LogType;

class AssociationRequiredMissingLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $requiredFor;

    public function __construct(string $runId, string $requiredFor, string $entity, string $sourceId)
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
        return LogType::ASSOCIATION_REQUIRED_MISSING;
    }

    public function getTitle(): string
    {
        return sprintf('Associated %s not found', $this->getEntity());
    }

    public function getDescriptionArguments(): array
    {
        return [
            'missingEntity' => $this->getEntity(),
            'requiredFor' => $this->requiredFor,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf(
            'The %s for the %s can not be found.',
            $args['missingEntity'],
            $args['requiredFor']
        );
    }
}
