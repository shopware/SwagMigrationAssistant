<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class MappingDeltaResult extends Struct
{
    public function __construct(
        private readonly array $migrationData = [],
        private readonly array $preloadIds = []
    ) {
    }

    public function getMigrationData(): array
    {
        return $this->migrationData;
    }

    public function getPreloadIds(): array
    {
        return $this->preloadIds;
    }
}
