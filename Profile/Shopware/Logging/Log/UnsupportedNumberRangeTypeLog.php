<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;
use SwagMigrationAssistant\Profile\Shopware\Logging\LogType;

class UnsupportedNumberRangeTypeLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $runId, string $type, ?string $entity = null, ?string $sourceId = null)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->type = $type;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return LogType::UNSUPPORTED_NUMBER_RANGE_TYPE;
    }

    public function getTitle(): string
    {
        return 'Unsupported number range type';
    }

    public function getDescriptionArguments(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getDescriptionArguments();

        return sprintf('NumberRange-Entity could not be converted because of unsupported type: %s.', $args['type']);
    }
}
