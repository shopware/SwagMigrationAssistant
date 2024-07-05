<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class AssociationRequiredMissingLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $requiredFor
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
            return 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING';
        }

        return \sprintf('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_%s', \mb_strtoupper($entity));
    }

    public function getTitle(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'Associated not found';
        }

        return \sprintf('Associated %s not found', $entity);
    }

    /**
     * @return array{missingEntity: ?string, requiredFor: string, sourceId: ?string}
     */
    public function getParameters(): array
    {
        return [
            'missingEntity' => $this->getEntity(),
            'requiredFor' => $this->requiredFor,
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The %s with the source id "%s" can not be found but is required for %s.',
            $args['missingEntity'],
            $args['sourceId'],
            $args['requiredFor']
        );
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING');
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING');
    }
}
