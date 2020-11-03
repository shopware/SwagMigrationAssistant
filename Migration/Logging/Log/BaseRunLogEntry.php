<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

abstract class BaseRunLogEntry implements LogEntryInterface
{
    /**
     * @var string
     */
    protected $runId;

    /**
     * @var ?string
     */
    protected $entity;

    /**
     * @var ?string
     */
    protected $sourceId;

    public function __construct(string $runId, ?string $entity = null, ?string $sourceId = null)
    {
        $this->runId = $runId;
        $this->entity = $entity;
        $this->sourceId = $sourceId;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function getSnippetRoot(): string
    {
        return 'swag-migration.index.error';
    }

    public function getTitleSnippet(): string
    {
        return \sprintf('%s.%s.title', $this->getSnippetRoot(), $this->getCode());
    }

    public function getDescriptionSnippet(): string
    {
        return \sprintf('%s.%s.description', $this->getSnippetRoot(), $this->getCode());
    }
}
