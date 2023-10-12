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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerWishlistDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class CustomerWishlistConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === CustomerWishlistDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CUSTOMER_WISHLIST,
            $data['id'],
            $converted['id']
        );

        $customerMapping = $this->getMappingIdFacade(DefaultEntities::CUSTOMER, $converted['customerId']);
        if ($customerMapping === null) {
            return new ConvertStruct(null, $data);
        }

        $salesChannelMapping = $this->getMappingIdFacade(DefaultEntities::SALES_CHANNEL, $converted['salesChannelId']);
        if ($salesChannelMapping === null) {
            return new ConvertStruct(null, $data);
        }

        $products = [];
        foreach ($converted['products'] as $product) {
            $productMapping = $this->getMappingIdFacade(DefaultEntities::PRODUCT, $product['productId']);
            if ($productMapping === null) {
                continue;
            }

            $products[] = $product;
        }

        if ($products === []) {
            return new ConvertStruct(null, $data);
        }

        $converted['products'] = $products;

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
