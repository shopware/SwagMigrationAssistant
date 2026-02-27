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
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertMainVariantRelationFailedLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
abstract class MainVariantRelationConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $connectionId = '';

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        if (!isset($data['id'], $data['ordernumber'])) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withSourceData($data)
                    ->withExceptionMessage('MainVariantRelation requires ID and order number, to be converted successful')
                    ->withExceptionTrace(\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2))
                    ->build(ConvertMainVariantRelationFailedLog::class)
            );

            return new ConvertStruct(null, $data);
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MAIN_VARIANT_RELATION,
            $data['id'],
            $context,
            $this->checksum
        );

        $mainProductMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['id'],
            $context
        );

        $variantProductMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $data['ordernumber'],
            $context
        );

        $mainProductId = null;

        if ($mainProductMapping !== null) {
            $this->mappingIds[] = $mainProductMapping['id'];
            $mainProductId = $mainProductMapping['entityId'];
        }

        $variantProductId = null;
        if ($variantProductMapping !== null) {
            $this->mappingIds[] = $variantProductMapping['id'];
            $variantProductId = $variantProductMapping['entityId'];
        }

        $converted = [];
        $converted['id'] = $mainProductId;
        $converted['variantListingConfig'] = [
            'displayParent' => true,
            'mainVariantId' => $variantProductId,
        ];
        unset($data['id'], $data['ordernumber']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }
}
