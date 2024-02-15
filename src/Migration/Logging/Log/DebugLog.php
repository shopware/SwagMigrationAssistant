<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class DebugLog implements LogEntryInterface
{
    /**
     * @param array<mixed> $logData
     */
    public function __construct(
        private readonly array $logData,
        private readonly ?string $runId
    ) {
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_DEBUG;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__DEBUG';
    }

    public function getTitle(): string
    {
        return 'Debug';
    }

    /**
     * @return array{logData: array<mixed>}
     */
    public function getParameters(): array
    {
        return [
            'logData' => $this->logData,
        ];
    }

    public function getDescription(): string
    {
        return (string) \json_encode($this->logData);
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

    public function getEntity(): ?string
    {
        return null;
    }

    public function getSourceId(): ?string
    {
        return null;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }
}
