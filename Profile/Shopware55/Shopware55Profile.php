<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\ProfileInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\AssociationEntityRequiredMissingException;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerExistsException;
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

        $catalogId = $migrationContext->getCatalogId();
        $salesChannelId = $migrationContext->getSalesChannelId();

        $converter = $this->converterRegistry->getConverter($entityName);
        $createData = [];
        foreach ($data as $item) {
            try {
                $convertStruct = $converter->convert($item, $context, $catalogId, $salesChannelId);

                $createData[] = [
                    'entity' => $entityName,
                    'profile' => $this->getName(),
                    'raw' => $item,
                    'converted' => $convertStruct->getConverted(),
                    'unmapped' => $convertStruct->getUnmapped(),
                ];
            } catch (
                ParentEntityForChildNotFoundException |
                AssociationEntityRequiredMissingException $exception
            ) {
                // TODO: Log error
                $createData[] = [
                    'entity' => $entityName,
                    'profile' => $this->getName(),
                    'raw' => $item,
                    'converted' => null,
                    'unmapped' => $item,
                ];
                continue;
            }
        }

        if (\count($createData) === 0) {
            return 0;
        }

        $converter->writeMapping($context);

        /** @var EntityWrittenContainerEvent $writtenEvent */
        $writtenEvent = $this->migrationDataRepo->upsert($createData, $context);

        $event = $writtenEvent->getEventByDefinition(SwagMigrationDataDefinition::class);

        return \count($event->getIds());
    }
}
