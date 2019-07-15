<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\LogType;

class CannotReadEntityCountLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $condition;

    public function __construct(string $runUuid, string $entity, string $table, ?string $condition = null)
    {
        parent::__construct($runUuid, $entity);
        $this->table = $table;
        $this->condition = $condition ?? '';
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return LogType::COULD_NOT_READ_ENTITY_COUNT;
    }

    public function getTitle(): string
    {
        return 'Could not read entity count';
    }

    public function getDescriptionArguments(): array
    {
        return [
            'entity' => $this->getEntity(),
            'table' => $this->table,
            'condition' => $this->condition,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf('Total count for entity %s could not be read. Make the the table %s exists in your source system and the optional condition "%s" is valid.',
            $args['entity'],
            $args['table'],
            $args['condition']
        );
    }
}
