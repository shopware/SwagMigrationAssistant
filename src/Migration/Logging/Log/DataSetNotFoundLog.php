<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class DataSetNotFoundLog extends BaseRunLogEntry
{
    public function __construct(
        string $runUuid,
        string $entity,
        string $sourceId,
        private readonly string $profileName
    ) {
        parent::__construct($runUuid, $entity, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__DATASET_NOT_FOUND';
    }

    public function getTitle(): string
    {
        return 'DataSet not found';
    }

    /**
     * @return array{profileName: string, entity: ?string, sourceId: ?string}
     */
    public function getParameters(): array
    {
        return [
            'profileName' => $this->profileName,
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'DataSet for profile "%s" and entity "%s" not found. Entity with id "%s" could not be processed.',
            $args['profileName'],
            $args['entity'],
            $args['sourceId']
        );
    }
}
