<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Struct\Struct;

class TotalStruct extends Struct
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var int
     */
    protected $total;

    public function __construct(string $entityName, int $total = 0)
    {
        $this->entityName = $entityName;
        $this->total = $total;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
