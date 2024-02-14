<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class ProductConverter extends ShopwareMediaConverter
{
    private ?string $sourceDefaultCurrencyUuid;

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === ProductDataSet::getEntity();
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $product) {
            if (isset($product['media'])) {
                foreach ($product['media'] as $media) {
                    $mediaIds[] = $media['media']['id'];
                }
            }

            if (isset($product['configuratorSettings'])) {
                foreach ($product['configuratorSettings'] as $setting) {
                    if (isset($setting['media'])) {
                        $mediaIds[] = $setting['media']['id'];
                    }
                }
            }

            if (isset($product['downloads'])) {
                foreach ($product['downloads'] as $download) {
                    $mediaIds[] = $download['media']['id'];
                }
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT,
            $data['id'],
            $converted['id']
        );

        $this->sourceDefaultCurrencyUuid = $this->getMappingIdFacade(
            DefaultEntities::CURRENCY,
            Defaults::CURRENCY
        );

        if (isset($converted['price'])) {
            $this->updateAssociationIds(
                $converted['price'],
                DefaultEntities::CURRENCY,
                'currencyId',
                DefaultEntities::PRODUCT
            );

            $this->checkDefaultCurrency($converted, 'price');
        }

        if (isset($converted['translations'])) {
            $this->updateAssociationIds(
                $converted['translations'],
                DefaultEntities::LANGUAGE,
                'languageId',
                DefaultEntities::PRODUCT
            );
        }

        if (isset($converted['propertyIds'])) {
            $this->reformatMtoNAssociation(
                $converted,
                'propertyIds',
                'properties'
            );
        }

        if (isset($converted['optionIds'])) {
            $this->reformatMtoNAssociation(
                $converted,
                'optionIds',
                'options'
            );
        }

        if (isset($converted['taxId'])) {
            $converted['taxId'] = $this->getMappingIdFacade(
                DefaultEntities::TAX,
                $converted['taxId']
            );
        }

        if (isset($converted['manufacturerId'])) {
            $converted['manufacturerId'] = $this->getMappingIdFacade(
                DefaultEntities::PRODUCT_MANUFACTURER,
                $converted['manufacturerId']
            );
        }

        if (isset($converted['unitId'])) {
            $converted['unitId'] = $this->getMappingIdFacade(
                DefaultEntities::UNIT,
                $converted['unitId']
            );
        }

        if (isset($converted['purchasePrices'])) {
            $this->updateAssociationIds(
                $converted['purchasePrices'],
                DefaultEntities::CURRENCY,
                'currencyId',
                DefaultEntities::PRODUCT
            );

            $this->checkDefaultCurrency($converted, 'purchasePrices');
        }

        if (isset($converted['configuratorSettings'])) {
            foreach ($converted['configuratorSettings'] as &$setting) {
                if (isset($setting['price'])) {
                    $this->updateAssociationIds(
                        $setting['price'],
                        DefaultEntities::CURRENCY,
                        'currencyId',
                        DefaultEntities::PRODUCT
                    );

                    $this->checkDefaultCurrency($setting, 'price');
                }

                if (isset($setting['media'])) {
                    $this->updateMediaAssociation($setting['media']);
                }
            }
            unset($setting);
        }

        if (isset($converted['prices'])) {
            foreach ($converted['prices'] as &$price) {
                $this->updateAssociationIds(
                    $price['price'],
                    DefaultEntities::CURRENCY,
                    'currencyId',
                    DefaultEntities::PRODUCT
                );
            }
            unset($price);

            foreach ($converted['prices'] as &$priceRule) {
                $this->checkDefaultCurrency($priceRule, 'price');
            }
            unset($priceRule);
        }

        if (isset($converted['deliveryTimeId'])) {
            $converted['deliveryTimeId'] = $this->getMappingIdFacade(
                DefaultEntities::DELIVERY_TIME,
                $converted['deliveryTimeId']
            );
        }

        if (isset($converted['media'])) {
            foreach ($converted['media'] as &$mediaAssociation) {
                $this->updateMediaAssociation($mediaAssociation['media']);
            }
            unset($mediaAssociation);
        }

        if (isset($converted['downloads'])) {
            foreach ($converted['downloads'] as &$download) {
                $this->updateMediaAssociation($download['media'], DefaultEntities::PRODUCT_DOWNLOAD);
            }
            unset($download);
        }

        if (isset($converted['visibilities'])) {
            $this->updateAssociationIds(
                $converted['visibilities'],
                DefaultEntities::SALES_CHANNEL,
                'salesChannelId',
                DefaultEntities::PRODUCT,
                false
            );
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    private function checkDefaultCurrency(array &$source, string $key): void
    {
        // If the default currency of source and destination is identically, there is no need to add a default price
        if ($this->sourceDefaultCurrencyUuid === Defaults::CURRENCY) {
            return;
        }

        $hasDefaultPrice = false;
        $defaultPrice = [];
        foreach ($source[$key] as $price) {
            if ($price['currencyId'] === Defaults::CURRENCY) {
                $hasDefaultPrice = true;
            }

            if ($price['currencyId'] === $this->sourceDefaultCurrencyUuid) {
                $defaultPrice = $price;
            }
        }

        if ($defaultPrice !== [] && $hasDefaultPrice !== true) {
            $defaultPrice['currencyId'] = Defaults::CURRENCY;
            $source[$key][] = $defaultPrice;
        }
    }
}
