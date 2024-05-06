<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\NumberRange\NumberRangeCollection;
use Shopware\Core\System\NumberRange\NumberRangeEntity;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedNumberRangeTypeLog;

#[Package('services-settings')]
abstract class NumberRangeConverter extends ShopwareConverter
{
    /**
     * @var array<string, string>
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
     * @var EntityCollection<NumberRangeEntity>|null
     */
    protected ?EntityCollection $numberRangeTypes = null;

    protected string $connectionId;

    /**
     * @param EntityRepository<NumberRangeCollection> $numberRangeTypeRepo
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected EntityRepository $numberRangeTypeRepo
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        if (!$this->numberRangeTypes instanceof EntityCollection) {
            $this->numberRangeTypes = $this->numberRangeTypeRepo->search(new Criteria(), $context)->getEntities();
        }

        if (!\array_key_exists($data['name'], self::TYPE_MAPPING)) {
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

        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return new ConvertStruct(null, $data);
        }
        $this->connectionId = $connection->getId();

        $converted = [];
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
            $this->setNumberRangeTranslation($converted, $data, $context);
            $this->convertValue($converted, 'name', $data, 'name', self::TYPE_STRING);
            $this->convertValue($converted, 'description', $data, 'desc', self::TYPE_STRING);

            $this->setNumberRangeSalesChannels($converted, $context);
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

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        $mainMapping = $this->mainMapping['id'] ?? null;

        return new ConvertStruct($converted, $returnData, $mainMapping);
    }

    /**
     * @param array<mixed> $data
     */
    protected function getUuid(array $data, MigrationContextInterface $migrationContext, Context $context): string
    {
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $context
        );

        if ($mapping !== null) {
            $this->mainMapping = $mapping;

            return (string) $mapping['entityUuid'];
        }

        // use global number range uuid for products if available
        if ($data['name'] === 'articleordernumber') {
            $this->mappingService->getNumberRangeUuid('product', $data['id'], $this->checksum, $migrationContext, $context);
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $context,
            $this->checksum
        );

        return (string) $this->mainMapping['entityUuid'];
    }

    protected function getProductNumberRangeTypeUuid(string $type): ?string
    {
        if (!$this->numberRangeTypes instanceof EntityCollection) {
            return null;
        }

        $collection = $this->numberRangeTypes->filterByProperty('technicalName', self::TYPE_MAPPING[$type]);

        $first = $collection->first();
        if ($first === null) {
            return null;
        }

        return $first->getId();
    }

    protected function getGlobal(string $name): bool
    {
        return $name === 'articleordernumber';
    }

    /**
     * @param array<mixed> $converted
     * @param array<mixed> $data
     */
    protected function setNumberRangeTranslation(
        array &$converted,
        array $data,
        Context $context
    ): void {
        $language = $this->mappingService->getDefaultLanguage($context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $data['_locale']) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['number_range_id'] = $converted['id'];
        $localeTranslation['name'] = (string) $data['desc'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::NUMBER_RANGE_TRANSLATION,
            $data['id'] . ':' . $data['_locale'],
            $context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $converted['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<mixed> $converted
     */
    protected function setNumberRangeSalesChannels(array &$converted, Context $context): void
    {
        $salesChannelIds = $this->mappingService->getMigratedSalesChannelUuids($this->connectionId, $context);
        $numberRangeSalesChannels = [];

        foreach ($salesChannelIds as $saleChannelId) {
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::NUMBER_RANGE_SALES_CHANNEL,
                $converted['id'] . ':' . $saleChannelId,
                $context
            );
            $numberRangeSalesChannel = [];
            $numberRangeSalesChannel['id'] = $mapping['entityUuid'];
            $numberRangeSalesChannel['numberRangeId'] = $converted['id'];
            $numberRangeSalesChannel['salesChannelId'] = $saleChannelId;
            $numberRangeSalesChannel['numberRangeTypeId'] = $converted['typeId'];
            $numberRangeSalesChannels[] = $numberRangeSalesChannel;
            $this->mappingIds[] = $mapping['id'];
        }

        $converted['numberRangeSalesChannels'] = $numberRangeSalesChannels;
    }
}
