<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class TaxConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;
        $taxId = $this->mappingService->getTaxUuid(
            $this->connectionId,
            $data['taxRate'],
            $this->context
        );

        if ($taxId !== null) {
            $converted['id'] = $taxId;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::TAX,
            $data['id'],
            $converted['id']
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
