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
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\TaxRuleDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class TaxRuleConverter extends ShopwareConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        private readonly TaxRuleLookup $taxRuleLookup,
        private readonly TaxRuleTypeLookup $taxRuleTypeLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

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

        $taxRuleTypeMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::TAX_RULE_TYPE,
            $converted['taxRuleTypeId'],
            $this->context
        );

        if ($taxRuleTypeMapping) {
            $taxRuleTypeUuid = $taxRuleTypeMapping['entityUuid'];
        } else {
            $taxRuleTypeUuid = $this->taxRuleTypeLookup->get(
                $converted['type']['technicalName'] ?? '',
                $this->context
            );

            $this->mappingService->createMapping(
                $this->connectionId,
                DefaultEntities::TAX_RULE_TYPE,
                $data['type']['id'],
                $this->checksum,
                null,
                $taxRuleTypeUuid,
            );
        }

        // new types can not be created due to write protection on technical name
        if ($taxRuleTypeUuid === null) {
            return new ConvertStruct(null, $converted);
        }
        unset($converted['type']);
        $converted['taxRuleTypeId'] = $taxRuleTypeUuid;

        $taxRuleId = $this->taxRuleLookup->get(
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
