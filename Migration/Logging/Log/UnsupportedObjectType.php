<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

class UnsupportedObjectType extends BaseRunLogEntry
{
    /**
     * @var string string
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
        return 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_OBJECT_TYPE';
    }

    public function getTitle(): string
    {
        return 'Unsupported object type';
    }

    public function getParameters(): array
    {
        return [
            'objectType' => $this->type,
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            '%s of object type "%s" with source id "%s" could not be converted.',
            $args['entity'],
            $args['objectType'],
            $args['sourceId']
        );
    }
}
