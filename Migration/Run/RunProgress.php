<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class RunProgress extends Struct
{
    protected string $id;

    /**
     * @var EntityProgress[]
     */
    protected array $entities;

    protected int $currentCount;

    protected int $total;

    protected bool $processMediaFiles;

    protected string $snippet;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return EntityProgress[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function setEntities(array $entities): void
    {
        $this->entities = $entities;
    }

    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }

    public function setCurrentCount(int $currentCount): void
    {
        $this->currentCount = $currentCount;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getSnippet(): string
    {
        return $this->snippet;
    }

    public function setSnippet(string $snippet): void
    {
        $this->snippet = $snippet;
    }

    public function isProcessMediaFiles(): bool
    {
        return $this->processMediaFiles;
    }

    public function setProcessMediaFiles(bool $processMediaFiles): void
    {
        $this->processMediaFiles = $processMediaFiles;
    }
}
