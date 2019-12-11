<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Struct\Struct;

class EntityProgress extends Struct
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var int
     */
    protected $currentCount;

    /**
     * @var int
     */
    protected $total;

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
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
}
