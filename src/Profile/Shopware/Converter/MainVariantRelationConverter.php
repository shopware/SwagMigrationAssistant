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
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\SwagMigrationLogBuilder;
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

        if ($mainProductMapping === null) {
            $this->addAssociationRequiredLog(
                $migrationContext,
                'id',
                DefaultEntities::PRODUCT_CONTAINER,
                $data
            );

            return new ConvertStruct(null, $data);
        }

        if ($variantProductMapping === null) {
            $this->addAssociationRequiredLog(
                $migrationContext,
                'ordernumber',
                DefaultEntities::PRODUCT,
                $data
            );

            return new ConvertStruct(null, $data);
        }

        $this->mappingIds[] = $mainProductMapping['id'];
        $this->mappingIds[] = $variantProductMapping['id'];

        $converted = [];
        $converted['id'] = $mainProductMapping['entityUuid'];

        $converted['variantListingConfig'] = [
            'displayParent' => true,
            'mainVariantId' => $variantProductMapping['entityUuid'],
        ];
        unset($data['id'], $data['ordernumber']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function addAssociationRequiredLog(MigrationContextInterface $migrationContext, string $field, string $entity, array $data): void
    {
        $this->loggingService->addLogEntry( // TODO: add optional fields
            SwagMigrationLogBuilder::fromMigrationContext($migrationContext)
                ->withEntityName($entity)
                ->withFieldName($field)
                ->withFieldSourcePath($field)
                ->withSourceData([$data])
                ->build(AssociationRequiredMissingLog::class)
        );
    }
}
