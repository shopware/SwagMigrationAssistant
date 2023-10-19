<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class TotalStruct extends Struct
{
    public function __construct(
        protected string $entityName,
        protected int $total = 0
    ) {
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
