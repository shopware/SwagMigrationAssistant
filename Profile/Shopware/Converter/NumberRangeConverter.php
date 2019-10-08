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
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedNumberRangeTypeLog;

abstract class NumberRangeConverter extends ShopwareConverter
{
    /**
     * @var array
     */
    protected const TYPE_MAPPING = [
        'user' => 'customer',
        'invoice' => 'order',
        'articleordernumber' => 'product',
        'doc_0' => 'document_invoice',
        'doc_1' => 'document_delivery_note',
        'doc_2' => 'document_credit_note',
    ];

    /**
     * @var EntityRepositoryInterface
     */
    protected $numberRangeTypeRepo;

    /**
     * @var EntityCollection
     */
    protected $numberRangeTypes;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        EntityRepositoryInterface $numberRangeTypeRepo
    ) {
        parent::__construct($mappingService, $loggingService);

        $this->numberRangeTypeRepo = $numberRangeTypeRepo;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        if (empty($this->numberRangeTypes)) {
            $this->numberRangeTypes = $this->numberRangeTypeRepo->search(new Criteria(), $context)->getEntities();
        }

        if (!array_key_exists($data['name'], self::TYPE_MAPPING)) {
            $this->loggingService->addLogEntry(
                new UnsupportedNumberRangeTypeLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::NUMBER_RANGE,
                    $data['id'],
                    $data['name']
                )
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
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $data, $this->mainMapping['id']);
    }

    protected function getUuid(array $data, MigrationContextInterface $migrationContext, Context $context): string
    {
        $mapping = $this->mappingService->getMapping(
            $migrationContext->getConnection()->getId(),
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $context
        );

        if ($mapping !== null) {
            $this->mainMapping = $mapping;

            return $mapping['entityUuid'];
        }

        // use global number range uuid for products if available
        if ($data['name'] === 'articleordernumber') {
            $this->mappingService->getNumberRangeUuid('product', $data['id'], $this->checksum, $migrationContext, $context);
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $migrationContext->getConnection()->getId(),
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $context,
            $this->checksum
        );

        return $this->mainMapping['entityUuid'];
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

        $mapping = $this->mappingService->getOrCreateMapping(
            $connectionId,
            DefaultEntities::NUMBER_RANGE_TRANSLATION,
            $data['id'] . ':' . $data['_locale'],
            $context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];

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
            $mapping = $this->mappingService->getOrCreateMapping(
                $connectionId,
                DefaultEntities::NUMBER_RANGE_SALES_CHANNEL,
                $converted['id'] . ':' . $saleschannelId,
                $context
            );
            $numberRangeSaleschannel = [];
            $numberRangeSaleschannel['id'] = $mapping['entityUuid'];
            $numberRangeSaleschannel['numberRangeId'] = $converted['id'];
            $numberRangeSaleschannel['salesChannelId'] = $saleschannelId;
            $numberRangeSaleschannel['numberRangeTypeId'] = $converted['typeId'];
            $numberRangeSaleschannels[] = $numberRangeSaleschannel;
            $this->mappingIds[] = $mapping['id'];
        }

        $converted['numberRangeSalesChannels'] = $numberRangeSaleschannels;
    }
}
