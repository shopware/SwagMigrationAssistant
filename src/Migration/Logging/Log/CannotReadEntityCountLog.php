<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class CannotReadEntityCountLog extends BaseRunLogEntry
{
    public function __construct(
        string $runUuid,
        string $entity,
        private readonly string $table,
        private readonly ?string $condition,
        private readonly string $exceptionCode,
        private readonly string $exceptionMessage
    ) {
        parent::__construct($runUuid, $entity);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__COULD_NOT_READ_ENTITY_COUNT';
    }

    public function getTitle(): string
    {
        return 'Could not read entity count';
    }

    /**
     * @return array{entity: ?string, table: string, condition: ?string, exceptionCode: string, exceptionMessage: string}
     */
    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'table' => $this->table,
            'condition' => $this->condition,
            'exceptionCode' => $this->exceptionCode,
            'exceptionMessage' => $this->exceptionMessage,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'Total count for entity %s could not be read. Make sure the table %s exists in your source system and the optional condition "%s" is valid. Exception message: %s',
            $args['entity'],
            $args['table'],
            $args['condition'],
            $args['exceptionMessage']
        );
    }
}
