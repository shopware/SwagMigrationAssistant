<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\TaxRuleDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class TaxRuleConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === TaxRuleDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $taxId = $this->getMappingIdFacade(
            DefaultEntities::TAX,
            $converted['taxId']
        );
        if ($taxId === null) {
            return new ConvertStruct(null, $converted);
        }
        $converted['taxId'] = $taxId;

        $countryId = $this->getMappingIdFacade(
            DefaultEntities::COUNTRY,
            $converted['countryId']
        );
        if ($countryId === null) {
            return new ConvertStruct(null, $converted);
        }
        $converted['countryId'] = $countryId;

        $taxRuleTypeId = $this->mappingService->getTaxRuleTypeUuidByCriteria(
            $this->connectionId,
            $converted['taxRuleTypeId'],
            $converted['type']['technicalName'] ?? '',
            $this->context
        );
        // new types can not be created due to write protection on technical name
        if ($taxRuleTypeId === null) {
            return new ConvertStruct(null, $converted);
        }
        unset($converted['type']);
        $converted['taxRuleTypeId'] = $taxRuleTypeId;

        $taxRuleId = $this->mappingService->getTaxRuleUuidByCriteria(
            $this->connectionId,
            $converted['id'],
            $converted['taxId'],
            $converted['countryId'],
            $converted['taxRuleTypeId'],
            $this->context
        );

        if ($taxRuleId !== null) {
            $converted['id'] = $taxRuleId;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::TAX_RULE,
            $data['id'],
            $converted['id']
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
