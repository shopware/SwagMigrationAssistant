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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class CustomerConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === CustomerDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CUSTOMER,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['lastPaymentMethodId'])) {
            $converted['lastPaymentMethodId'] = $this->getMappingIdFacade(DefaultEntities::PAYMENT_METHOD, $converted['lastPaymentMethodId']);
        }

        $converted['defaultPaymentMethodId'] = $this->getMappingIdFacade(DefaultEntities::PAYMENT_METHOD, $converted['defaultPaymentMethodId']);
        $converted['salutationId'] = $this->getMappingIdFacade(DefaultEntities::SALUTATION, $converted['salutationId']);
        $converted['languageId'] = $this->getMappingIdFacade(DefaultEntities::LANGUAGE, $converted['languageId']);

        $this->updateAssociationIds($converted['addresses'], DefaultEntities::COUNTRY, 'countryId', DefaultEntities::CUSTOMER);
        $this->updateAssociationIds($converted['addresses'], DefaultEntities::SALUTATION, 'salutationId', DefaultEntities::CUSTOMER);
        $this->updateAssociationIds($converted['addresses'], DefaultEntities::COUNTRY_STATE, 'countryStateId', DefaultEntities::COUNTRY_STATE);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
