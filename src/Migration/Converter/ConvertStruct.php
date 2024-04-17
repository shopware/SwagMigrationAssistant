<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class ConvertStruct extends Struct
{
    /**
     * @param array<mixed>|null $converted
     * @param array<mixed>|null $unmapped
     */
    public function __construct(
        private readonly ?array $converted,
        private readonly ?array $unmapped,
        private readonly ?string $mappingUuid = null
    ) {
    }

    /**
     * @return array<mixed>|null
     */
    public function getConverted(): ?array
    {
        return $this->converted;
    }

    /**
     * @return array<mixed>|null
     */
    public function getUnmapped(): ?array
    {
        return $this->unmapped;
    }

    public function getMappingUuid(): ?string
    {
        return $this->mappingUuid;
    }
}
