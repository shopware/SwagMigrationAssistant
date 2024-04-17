<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class UnknownEntityLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $requiredForEntity,
        private readonly string $requiredForSourceId
    ) {
        parent::__construct($runId, $entity, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'SWAG_MIGRATION_ENTITY_UNKNOWN';
        }

        return \sprintf('SWAG_MIGRATION_%s_ENTITY_UNKNOWN', \mb_strtoupper($entity));
    }

    public function getTitle(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'Cannot find entity';
        }

        return \sprintf('Cannot find %s', $entity);
    }

    /**
     * @return array{requiredForEntity: string, requiredForSourceId: string, entity: ?string, sourceId: ?string}
     */
    public function getParameters(): array
    {
        return [
            'requiredForEntity' => $this->requiredForEntity,
            'requiredForSourceId' => $this->requiredForSourceId,
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The %s entity with the source id "%s" cannot find the depended %s entity with the source id "%s".',
            $args['requiredForEntity'],
            $args['requiredForSourceId'],
            $args['entity'],
            $args['sourceId']
        );
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_UNKNOWN');
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_UNKNOWN');
    }
}
