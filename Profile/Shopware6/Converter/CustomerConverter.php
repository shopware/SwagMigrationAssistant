<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class CustomerConverter extends ShopwareConverter
{
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
