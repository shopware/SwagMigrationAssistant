<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class EmptyNecessaryFieldRunLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $emptyField
    ) {
        parent::__construct($runId, $entity, $sourceId);
    }

    public function getCode(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD';
        }

        return \sprintf('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_%s', \mb_strtoupper($entity));
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getTitle(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'The entity has one or more empty necessary fields';
        }

        return \sprintf('The %s entity has one or more empty necessary fields', $entity);
    }

    /**
     * @return array{entity: ?string, sourceId: ?string, emptyField: string}
     */
    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'emptyField' => $this->emptyField,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The %s entity with the source id %s does not have the necessary data for the field(s): %s',
            $args['entity'],
            $args['sourceId'],
            $args['emptyField']
        );
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_EMPTY_NECESSARY_DATA_FIELDS');
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_EMPTY_NECESSARY_DATA_FIELDS');
    }
}
