<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;

// ToDo: use and test this class
#[Package('services-settings')]
class MappingServiceV2Builder
{
    /**
     * @param string $connectionId
     * @param array<int, PremappingStruct> $premapping
     * @return MappingServiceV2
     */
    public static function buildFromPremapping(string $connectionId, array $premapping): MappingServiceV2
    {
        /** @var array<string, string> $mappings */
        $mappings = [];
        /** @var array<string, string> $values */
        $values = [];

        foreach ($premapping as $pre) {
            $entity = $pre->getEntity();
            foreach ($pre->getMapping() as $map) {
                $oldIdentifier = $map->getSourceId();
                $entityUuid = $map->getDestinationUuid();

                if (Uuid::isValid($entityUuid)) {
                    $mappings[$entity . $oldIdentifier] = $entityUuid;
                    continue;
                }

                $values[$oldIdentifier] = $entityUuid;
            }
        }

        return new MappingServiceV2(
            $connectionId,
            $mappings,
            $values
        );
    }
}
