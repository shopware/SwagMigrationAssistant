<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class SalesChannelConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SalesChannelDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        if ($converted['id'] === TestDefaults::SALES_CHANNEL) {
            $mapping = $this->getMappingIdFacade(DefaultEntities::SALES_CHANNEL, $data['id']);
            $converted['id'] = $mapping ?? Uuid::randomHex();
            $converted['name'] .= ' (Migration)';

            foreach ($converted['translations'] as &$translation) {
                $translation['salesChannelId'] = $converted['id'];
                $translation['name'] .= ' (Migration)';
            }
            unset($translation);
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SALES_CHANNEL,
            $data['id'],
            $converted['id']
        );

        $this->updateEntityAssociation(
            $converted,
            'countries',
            DefaultEntities::COUNTRY
        );

        $this->updateEntityAssociation(
            $converted,
            'currencies',
            DefaultEntities::CURRENCY
        );

        $this->updateEntityAssociation(
            $converted,
            'languages',
            DefaultEntities::LANGUAGE
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::SALES_CHANNEL
        );

        $this->updateEntityAssociation(
            $converted,
            'shippingMethods',
            DefaultEntities::SHIPPING_METHOD
        );

        if (isset($data['domains'])) {
            $this->updateAssociationIds(
                $converted['domains'],
                DefaultEntities::LANGUAGE,
                'languageId',
                DefaultEntities::SALES_CHANNEL
            );

            $this->updateAssociationIds(
                $converted['domains'],
                DefaultEntities::CURRENCY,
                'currencyId',
                DefaultEntities::SALES_CHANNEL
            );

            $this->updateAssociationIds(
                $converted['domains'],
                DefaultEntities::SNIPPET_SET,
                'snippetSetId',
                DefaultEntities::SALES_CHANNEL
            );
        }

        $converted['customerGroupId'] = $this->getMappingIdFacade(DefaultEntities::CUSTOMER_GROUP, $data['customerGroupId']);
        $converted['navigationCategoryId'] = $this->getMappingIdFacade(DefaultEntities::CATEGORY, $data['navigationCategoryId']);
        if (isset($data['footerCategoryId'])) {
            $converted['footerCategoryId'] = $this->getMappingIdFacade(DefaultEntities::CATEGORY, $data['footerCategoryId']);
        }
        if (isset($data['serviceCategoryId'])) {
            $converted['serviceCategoryId'] = $this->getMappingIdFacade(DefaultEntities::CATEGORY, $data['serviceCategoryId']);
        }

        $converted['languageId'] = $this->getMappingIdFacade(DefaultEntities::LANGUAGE, $data['languageId']);
        $converted['currencyId'] = $this->getMappingIdFacade(DefaultEntities::CURRENCY, $data['currencyId']);
        $converted['shippingMethodId'] = $this->getMappingIdFacade(DefaultEntities::SHIPPING_METHOD, $data['shippingMethodId']);
        $converted['countryId'] = $this->getMappingIdFacade(DefaultEntities::COUNTRY, $data['countryId']);
        $converted['paymentMethodId'] = $this->getMappingIdFacade(DefaultEntities::PAYMENT_METHOD, $data['paymentMethodId']);

        if (isset($converted['paymentMethodIds'])) {
            $this->reformatMtoNAssociation(
                $converted,
                'paymentMethodIds',
                'paymentMethods'
            );

            $this->updateAssociationIds(
                $converted['paymentMethods'],
                DefaultEntities::PAYMENT_METHOD,
                'id',
                DefaultEntities::SALES_CHANNEL
            );
        }

        unset(
            // ToDo implement if these associations are migrated
            $converted['mailHeaderFooterId']
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
