<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class CustomerWishlistConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->migrationContext = $migrationContext;

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_WISHLIST,
            $data['userID'],
            $context,
            $this->checksum
        );

        $customerMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::CUSTOMER, $data['userID'], $context);
        if ($customerMapping === null) {
            return new ConvertStruct(null, $data);
        }

        $productMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::PRODUCT, $data['ordernumber'], $context);
        if ($productMapping === null) {
            return new ConvertStruct(null, $data);
        }

        $shopMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::SALES_CHANNEL, $data['subshopID'], $context);
        if ($shopMapping === null) {
            return new ConvertStruct(null, $data);
        }

        $this->mappingIds[] = $customerMapping['id'];
        $this->mappingIds[] = $productMapping['id'];

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];
        $converted['customerId'] = $customerMapping['entityUuid'];
        $converted['salesChannelId'] = $shopMapping['entityUuid'];
        $converted['products'][] = [
            'productId' => $productMapping['entityUuid'],
        ];

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
