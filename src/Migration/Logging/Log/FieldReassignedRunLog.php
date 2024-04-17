<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class FieldReassignedRunLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $emptyField,
        private readonly string $replacementField
    ) {
        parent::__construct($runId, $entity, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED';
        }

        return \sprintf('SWAG_MIGRATION_%s_ENTITY_FIELD_REASSIGNED', \mb_strtoupper($entity));
    }

    public function getTitle(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'The entity has a field that was reassigned';
        }

        return \sprintf('The %s entity has a field that was reassigned', $entity);
    }

    /**
     * @return array{entity: ?string, sourceId: ?string, emptyField: string, replacementField: string}
     */
    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'emptyField' => $this->emptyField,
            'replacementField' => $this->replacementField,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The %s entity with the source id "%s" got the field %s replaced with %s.',
            $args['entity'],
            $args['sourceId'],
            $args['emptyField'],
            $args['replacementField']
        );
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED');
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION_ENTITY_FIELD_REASSIGNED');
    }
}
