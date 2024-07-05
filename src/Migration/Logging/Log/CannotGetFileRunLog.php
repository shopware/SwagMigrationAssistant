<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use GuzzleHttp\Exception\RequestException;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class CannotGetFileRunLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $uri,
        private readonly ?RequestException $requestException = null,
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

    /**
     * @return array{entity: ?string, sourceId: ?string, uri: string}
     */
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

        $description = \sprintf(
            'The %s file with the uri "%s" and media id "%s" cannot be downloaded / copied.',
            $args['entity'],
            $args['uri'],
            $args['sourceId']
        );

        if ($this->requestException !== null) {
            $description .= \sprintf(
                ' The following request error occurred: %s',
                $this->requestException->getMessage()
            );
        }

        return $description;
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
