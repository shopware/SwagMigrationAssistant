<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class MainVariantRelationConverter extends ShopwareConverter
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $connectionId = '';

    /**
     * @var string
     */
    private $runUuid;

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->runUuid = $migrationContext->getRunUuid();
        $connection = $migrationContext->getConnection();
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        if (!isset($data['id'], $data['ordernumber'])) {
            return new ConvertStruct(null, $data);
        }

        $mainMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['id'],
            $context
        );

        $variantMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $data['ordernumber'],
            $context
        );

        if ($mainMapping === null) {
            $this->addAssociationRequiredLog(DefaultEntities::PRODUCT_CONTAINER, $data['id']);

            return new ConvertStruct(null, $data);
        }

        if ($variantMapping === null) {
            $this->addAssociationRequiredLog(DefaultEntities::PRODUCT, $data['ordernumber']);

            return new ConvertStruct(null, $data);
        }

        $this->mappingIds[] = $mainMapping['id'];
        $this->mappingIds[] = $variantMapping['id'];

        $converted = [];
        $converted['id'] = $mainMapping['entityUuid'];
        $converted['mainVariantId'] = $variantMapping['entityUuid'];
        unset($data['id'], $data['ordernumber']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }

        return new ConvertStruct($converted, $returnData);
    }

    private function addAssociationRequiredLog(string $requiredEntity, string $id): void
    {
        $this->loggingService->addLogEntry(
            new AssociationRequiredMissingLog(
                $this->runUuid,
                $requiredEntity,
                $id,
                DefaultEntities::MAIN_VARIANT_RELATION
            )
        );
    }
}
