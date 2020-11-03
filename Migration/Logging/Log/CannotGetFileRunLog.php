<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

class CannotGetFileRunLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $uri;

    public function __construct(string $runId, string $entity, string $sourceId, string $uri)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->uri = $uri;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'SWAG_MIGRATION_CANNOT_GET_FILE';
        }

        return \sprintf('SWAG_MIGRATION_CANNOT_GET_%s_FILE', \mb_strtoupper($entity));
    }

    public function getTitle(): string
    {
        $entity = $this->getEntity();
        if ($entity === null) {
            return 'The file cannot be downloaded / copied';
        }

        return \sprintf('The %s file cannot be downloaded / copied', $entity);
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'uri' => $this->uri,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The %s file with the uri "%s" and media id "%s" cannot be downloaded / copied.',
            $args['entity'],
            $args['uri'],
            $args['sourceId']
        );
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), 'SWAG_MIGRATION_CANNOT_GET_FILE');
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), 'SWAG_MIGRATION_CANNOT_GET_FILE');
    }
}
