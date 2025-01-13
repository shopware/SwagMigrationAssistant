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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductReviewDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class ProductReviewConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === ProductReviewDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT_REVIEW,
            $data['id'],
            $converted['id']
        );

        // required associations
        $converted['productId'] = $this->getMappingIdFacade(DefaultEntities::PRODUCT, $data['productId']);
        $converted['salesChannelId'] = $this->getMappingIdFacade(DefaultEntities::SALES_CHANNEL, $data['salesChannelId']);
        $converted['languageId'] = $this->getMappingIdFacade(DefaultEntities::LANGUAGE, $data['languageId']);

        // nullable associations
        if (isset($data['customerId'])) {
            $converted['customerId'] = $this->getMappingIdFacade(DefaultEntities::CUSTOMER, $data['customerId']);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
