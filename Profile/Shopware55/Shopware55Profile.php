<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use InvalidArgumentException;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use SwagMigrationNext\Migration\Asset\MediaFileServiceInterface;
use SwagMigrationNext\Migration\Converter\ConverterInterface;
use SwagMigrationNext\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\ProfileInterface;
use SwagMigrationNext\Profile\Shopware55\Exception\AssociationEntityRequiredMissingException;
use SwagMigrationNext\Profile\Shopware55\Exception\ParentEntityForChildNotFoundException;

class Shopware55Profile implements ProfileInterface
{
    public const PROFILE_NAME = 'shopware55';

    public const SOURCE_SYSTEM_NAME = 'Shopware';

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        RepositoryInterface $migrationDataRepo,
        ConverterRegistryInterface $converterRegistry,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->converterRegistry = $converterRegistry;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
    }

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function collectData(GatewayInterface $gateway, MigrationContext $migrationContext, Context $context): int
    {
        $entityName = $migrationContext->getEntity();
        $runId = $migrationContext->getRunUuid();

        try {
            /** @var array[] $data */
            $data = $gateway->read();
        } catch (\Exception $exception) {
            $this->loggingService->addError($runId, (string) $exception->getCode(), '', $exception->getMessage(), ['entity' => $entityName]);
            $this->loggingService->saveLogging($context);

            return 0;
        }

        if (\count($data) === 0) {
            return 0;
        }

        $converter = $this->converterRegistry->getConverter($migrationContext);
        $createData = $this->convertData($context, $data, $converter, $migrationContext, $entityName);

        if (\count($createData) === 0) {
            return 0;
        }

        $converter->writeMapping($context);
        $this->mediaFileService->writeMediaFile($context);
        $this->loggingService->saveLogging($context);

        /** @var EntityWrittenContainerEvent $writtenEvent */
        $writtenEvent = $this->migrationDataRepo->upsert($createData, $context);

        $event = $writtenEvent->getEventByDefinition(SwagMigrationDataDefinition::class);

        if (!$event) {
            return 0;
        }

        return \count($event->getIds());
    }

    public function readEnvironmentInformation(GatewayInterface $gateway): EnvironmentInformation
    {
        $environmentData = $gateway->readEnvironmentInformation();
        $environmentDataArray = $environmentData['environmentInformation'];
        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                self::SOURCE_SYSTEM_NAME,
                '',
                '',
                0,
                0,
                0,
                0,
                0,
                0,
                $environmentData['warning']['code'],
                $environmentData['warning']['detail'],
                $environmentData['error']['code'],
                $environmentData['error']['detail']
            );
        }

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        return new EnvironmentInformation(
            self::SOURCE_SYSTEM_NAME,
            $environmentDataArray['shopwareVersion'],
            $environmentDataArray['structure'][0]['host'],
            $environmentDataArray['categories'],
            $environmentDataArray['products'],
            $environmentDataArray['customers'],
            $environmentDataArray['orders'],
            $environmentDataArray['assets'],
            $environmentDataArray['translations'],
            $environmentData['warning']['code'],
            $environmentData['warning']['detail'],
            $environmentData['error']['code'],
            $environmentData['error']['detail']
        );
    }

    public function readEntityTotal(GatewayInterface $gateway, string $entityName): int
    {
        $environmentInformation = $this->readEnvironmentInformation($gateway);

        switch ($entityName) {
            case CategoryDefinition::getEntityName():
                return $environmentInformation->getCategoryTotal();
            case ProductDefinition::getEntityName():
                return $environmentInformation->getProductTotal();
            case CustomerDefinition::getEntityName():
                return $environmentInformation->getCustomerTotal();
            case OrderDefinition::getEntityName():
                return $environmentInformation->getOrderTotal();
            case MediaDefinition::getEntityName():
                return $environmentInformation->getAssetTotal();
//            case 'translation': TODO revert, when the core could handle translations correctly
//                return $environmentInformation->getTranslationTotal();
        }

        throw new InvalidArgumentException('No valid entity provided');
    }

    private function convertData(
        Context $context,
        array $data,
        ConverterInterface $converter,
        MigrationContext $migrationContext,
        string $entityName
    ): array {
        $runId = $migrationContext->getRunUuid();

        $createData = [];
        foreach ($data as $item) {
            try {
                $convertStruct = $converter->convert($item, $context, $migrationContext);
                $convertFailureFlag = empty($convertStruct->getConverted());

                $createData[] = [
                    'entity' => $entityName,
                    'runId' => $runId,
                    'raw' => $item,
                    'converted' => $convertStruct->getConverted(),
                    'unmapped' => $convertStruct->getUnmapped(),
                    'convertFailure' => $convertFailureFlag,
                ];
            } catch (ParentEntityForChildNotFoundException |
            AssociationEntityRequiredMissingException $exception
            ) {
                $this->loggingService->addError(
                    $runId,
                    (string) $exception->getCode(),
                    '',
                    $exception->getMessage(),
                    [
                        'entity' => $entityName,
                        'raw' => $item,
                    ]
                );

                $createData[] = [
                    'entity' => $entityName,
                    'runId' => $runId,
                    'raw' => $item,
                    'converted' => null,
                    'unmapped' => $item,
                    'convertFailure' => true,
                ];
                continue;
            }
        }

        return $createData;
    }
}
