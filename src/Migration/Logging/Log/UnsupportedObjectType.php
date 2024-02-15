<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class UnsupportedObjectType extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        private readonly string $type,
        string $entity,
        string $sourceId
    ) {
        parent::__construct($runId, $entity, $sourceId);
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

    /**
     * @return array{objectType: string, entity: ?string, sourceId: ?string}
     */
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
