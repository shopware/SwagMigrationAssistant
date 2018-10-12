<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use InvalidArgumentException;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\ProfileInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\AssociationEntityRequiredMissingException;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ParentEntityForChildNotFoundException;

class Shopware55Profile implements ProfileInterface
{
    public const PROFILE_NAME = 'shopware55';

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    public function __construct(
        RepositoryInterface $migrationDataRepo,
        ConverterRegistryInterface $converterRegistry
    ) {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->converterRegistry = $converterRegistry;
    }

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function collectData(GatewayInterface $gateway, MigrationContext $migrationContext, Context $context): int
    {
        $entityName = $migrationContext->getEntity();
        /** @var array[] $data */
        $data = $gateway->read($entityName, $migrationContext->getOffset(), $migrationContext->getLimit());

        if (\count($data) === 0) {
            return 0;
        }

        $converter = $this->converterRegistry->getConverter($entityName);
        $createData = $this->convertData($context, $data, $converter, $migrationContext, $entityName);

        if (\count($createData) === 0) {
            return 0;
        }

        $converter->writeMapping($context);

        /** @var EntityWrittenContainerEvent $writtenEvent */
        $writtenEvent = $this->migrationDataRepo->upsert($createData, $context);

        $event = $writtenEvent->getEventByDefinition(SwagMigrationDataDefinition::class);

        return \count($event->getIds());
    }

    public function readEnvironmentInformation(GatewayInterface $gateway): array
    {
        return $gateway->read('environment', 0, 0);
    }

    public function readEntityTotal(GatewayInterface $gateway, string $entityName): int
    {
        $data = $this->readEnvironmentInformation($gateway);

        switch ($entityName) {
            case ProductDefinition::getEntityName():
                $key = 'products';
                break;
            case CustomerDefinition::getEntityName():
                $key = 'customers';
                break;
            case CategoryDefinition::getEntityName():
                $key = 'categories';
                break;
            case MediaDefinition::getEntityName():
                $key = 'assets';
                break;
//            case 'translation': TODO revert, when the core could handle translations correctly
//                $key = 'translations';
//                break;
            case OrderDefinition::getEntityName():
                $key = 'orders';
                break;
            default:
                throw new InvalidArgumentException('No valid entity provided');
        }

        if (!isset($data['environmentInformation'][$key])) {
            return 0;
        }

        return $data['environmentInformation'][$key];
    }

    private function convertData(
        Context $context,
        array $data,
        ConverterInterface $converter,
        MigrationContext $migrationContext,
        string $entityName
    ): array {
        $runId = $migrationContext->getRunUuid();
        $catalogId = $migrationContext->getCatalogId();
        $salesChannelId = $migrationContext->getSalesChannelId();

        $createData = [];
        foreach ($data as $item) {
            try {
                $convertStruct = $converter->convert($item, $context, $catalogId, $salesChannelId);

                $createData[] = [
                    'entity' => $entityName,
                    'runId' => $runId,
                    'raw' => $item,
                    'converted' => $convertStruct->getConverted(),
                    'unmapped' => $convertStruct->getUnmapped(),
                ];
            } catch (ParentEntityForChildNotFoundException |
            AssociationEntityRequiredMissingException $exception
            ) {
                // TODO: Log error
                $createData[] = [
                    'entity' => $entityName,
                    'runId' => $runId,
                    'raw' => $item,
                    'converted' => null,
                    'unmapped' => $item,
                ];
                continue;
            }
        }

        return $createData;
    }
}
