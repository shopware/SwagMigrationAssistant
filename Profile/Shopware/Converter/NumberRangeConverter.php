<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\NumberRange\NumberRangeEntity;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\LogTypes;

abstract class NumberRangeConverter extends ShopwareConverter
{
    /**
     * @var array
     */
    protected const TYPE_MAPPING = [
        'user' => 'customer',
        'invoice' => 'order',
        'articleordernumber' => 'product',
        'doc_0' => 'document_inovice',
        'doc_1' => 'document_delivery_note',
        'doc_2' => 'document_credit_note',
    ];
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var EntityRepositoryInterface
     */
    protected $numberRangeTypeRepo;

    /**
     * @var EntityCollection
     */
    protected $numberRangeTypes;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    public function __construct(
        MappingServiceInterface $mappingService,
        EntityRepositoryInterface $numberRangeTypeRepo,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->numberRangeTypeRepo = $numberRangeTypeRepo;
        $this->loggingService = $loggingService;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        if (empty($this->numberRangeTypes)) {
            $this->numberRangeTypes = $this->numberRangeTypeRepo->search(new Criteria(), $context)->getEntities();
        }

        if (!array_key_exists($data['name'], self::TYPE_MAPPING)) {
            $this->loggingService->addWarning(
                $migrationContext->getRunUuid(),
                LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Unsupported number range type',
                sprintf('NumberRange-Entity could not be converted because of unsupported type: %s.', $data['name']),
                [
                    'id' => $data['id'],
                    'entity' => 'NumberRange',
                ],
                1
            );

            return new ConvertStruct(null, $data);
        }

        $converted['id'] = $this->getUuid($data, $migrationContext, $context);
        $converted['typeId'] = $this->getProductNumberRangeTypeUuid($data['name']);

        if (empty($converted['typeId'])) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::NUMBER_RANGE,
                $data['id'],
                'typeId'
            ));

            return new ConvertStruct(null, $data);
        }

        $converted['global'] = $this->getGlobal($data['name']);

        // only write name and description when not overriding global number range
        if ($converted['global'] === false) {
            $this->setNumberRangeTranslation($converted, $data, $migrationContext, $context);
            $this->convertValue($converted, 'name', $data, 'name', self::TYPE_STRING);
            $this->convertValue($converted, 'description', $data, 'desc', self::TYPE_STRING);

            $this->setNumberRangeSalesChannels($converted, $migrationContext, $context);
        }

        $converted['pattern'] = $data['prefix'] . '{n}';
        $converted['start'] = (int) $data['number'];
        // increment start value by 1 because of different handling in platform
        ++$converted['start'];

        unset(
            $data['id'],
            $data['prefix'],
            $data['number'],
            $data['_locale'],
            $data['name'],
            $data['desc']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    protected function getUuid(array $data, MigrationContextInterface $migrationContext, Context $context): string
    {
        $id = $this->mappingService->getUuid(
            $migrationContext->getConnection()->getId(),
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $context
        );

        if ($id !== null) {
            return $id;
        }

        // use global number range uuid for products if available
        if ($data['name'] === 'articleordernumber') {
            $id = $this->mappingService->getNumberRangeUuid('product', $data['id'], $migrationContext, $context);
        }

        if ($id === null) {
            $id = $this->mappingService->createNewUuid(
                $migrationContext->getConnection()->getId(),
                DefaultEntities::NUMBER_RANGE,
                $data['id'],
                $context
            );
        }

        return $id;
    }

    protected function getProductNumberRangeTypeUuid(string $type): ?string
    {
        $collection = $this->numberRangeTypes->filterByProperty('technicalName', self::TYPE_MAPPING[$type]);

        if (empty($collection->first())) {
            return null;
        }
        /** @var NumberRangeEntity $numberRange */
        $numberRange = $collection->first();

        return $numberRange->getId();
    }

    protected function getGlobal(string $name): bool
    {
        return $name === 'articleordernumber' ?? false;
    }

    protected function setNumberRangeTranslation(
        array &$converted,
        array $data,
        MigrationContextInterface $migrationContext,
        Context $context
    ): void {
        $language = $this->mappingService->getDefaultLanguage($context);
        if ($language->getLocale()->getCode() === $data['_locale']) {
            return;
        }

        $connectionId = $migrationContext->getConnection()->getId();

        $localeTranslation = [];
        $localeTranslation['number_range_id'] = $converted['id'];
        $localeTranslation['name'] = (string) $data['desc'];

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $connectionId,
            DefaultEntities::NUMBER_RANGE_TRANSLATION,
            $data['id'] . ':' . $data['_locale'],
            $context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($connectionId, $data['_locale'], $context);
        $localeTranslation['languageId'] = $languageUuid;

        $converted['translations'][$languageUuid] = $localeTranslation;
    }

    protected function setNumberRangeSalesChannels(array &$converted, MigrationContextInterface $migrationContext, Context $context): void
    {
        $connectionId = $migrationContext->getConnection()->getId();
        $saleschannelIds = $this->mappingService->getMigratedSalesChannelUuids($connectionId, $context);

        $numberRangeSaleschannels = [];

        foreach ($saleschannelIds as $saleschannelId) {
            $numberRangeSaleschannel = [];
            $numberRangeSaleschannel['id'] = $this->mappingService->createNewUuid(
                $connectionId,
                DefaultEntities::NUMBER_RANGE_SALES_CHANNEL,
                $converted['id'] . ':' . $saleschannelId,
                $context
            );
            $numberRangeSaleschannel['numberRangeId'] = $converted['id'];
            $numberRangeSaleschannel['salesChannelId'] = $saleschannelId;
            $numberRangeSaleschannel['numberRangeTypeId'] = $converted['typeId'];
            $numberRangeSaleschannels[] = $numberRangeSaleschannel;
        }

        $converted['numberRangeSalesChannels'] = $numberRangeSaleschannels;
    }
}
