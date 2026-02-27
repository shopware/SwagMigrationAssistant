<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

/**
 * @final
 *
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
class MigrationErrorResolutionContext
{
    /**
     * @internal
     *
     * @param array<int|string, array<int|string, mixed>> $data
     * @param array<string, list<MigrationFix>> $fixes
     */
    public function __construct(
        private array &$data,
        private array &$fixes,
        private readonly string $connectionId,
        private readonly string $runId,
        private readonly Context $context,
    ) {
    }

    /**
     * @return array<int|string, array<int|string, mixed>>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<int|string, array<int|string, mixed>> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, list<MigrationFix>>
     */
    public function getFixes(): array
    {
        return $this->fixes;
    }

    /**
     * @param array<string, list<MigrationFix>> $fixes
     */
    public function setFixes(array $fixes): void
    {
        $this->fixes = $fixes;
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
