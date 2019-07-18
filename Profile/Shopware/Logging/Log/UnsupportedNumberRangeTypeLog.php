<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

class UnsupportedNumberRangeTypeLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $runId, string $type, string $entity, string $sourceId)
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
        return 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_NUMBER_RANGE_TYPE';
    }

    public function getTitle(): string
    {
        return 'Unsupported number range type';
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'type' => $this->type,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return sprintf(
            'NumberRange-Entity with source id "%s" could not be converted because of unsupported type: %s.',
            $args['sourceId'],
            $args['type']
        );
    }
}
