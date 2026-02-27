<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertAssociationMissingLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
abstract class ProductPropertyRelationConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

    /**
     * @var array<mixed>
     */
    protected array $originalData;

    public function getSourceIdentifier(array $data): string
    {
        return $data['identifier'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->originalData = $data;

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        $productMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['productId'],
            $context
        );

        if ($productMapping === null) {
            $productMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_MAIN,
                $data['productId'],
                $context
            );
        }

        if ($productMapping === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(PropertyGroupOptionDefinition::ENTITY_NAME)
                    ->withFieldName('productId')
                    ->withSourceData($data)
                    ->build(ConvertAssociationMissingLog::class)
            );

            return new ConvertStruct(null, $data);
        }

        $this->mappingIds[] = $productMapping['id'];

        $optionMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            Hasher::hash(\mb_strtolower($data['name'] . '_' . $data['group']['name']), 'md5'),
            $context
        );

        if ($optionMapping !== null) {
            $this->mappingIds[] = $optionMapping['id'];
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_PROPERTY_RELATION,
            $data['identifier'],
            $context
        );

        $converted = [];
        $converted['id'] = $productMapping['entityId'] ?? null;
        $converted['properties'][] = [
            'id' => $optionMapping['entityId'] ?? null,
        ];
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
