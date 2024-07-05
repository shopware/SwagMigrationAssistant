<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\DeactivatedPackLanguageLog;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;

#[Package('services-settings')]
abstract class SalesChannelConverter extends ShopwareConverter
{
    protected string $mainLocale;

    protected Context $context;

    protected string $connectionId;

    protected string $oldIdentifier;

    /**
     * @param EntityRepository<PaymentMethodCollection> $paymentRepository
     * @param EntityRepository<ShippingMethodCollection> $shippingMethodRepo
     * @param EntityRepository<CountryCollection> $countryRepository
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepo
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected EntityRepository $paymentRepository,
        protected EntityRepository $shippingMethodRepo,
        protected EntityRepository $countryRepository,
        protected EntityRepository $salesChannelRepo,
        protected ?EntityRepository $languagePackRepo
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->migrationContext = $migrationContext;
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        $this->oldIdentifier = $data['id'];

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $data['id'],
            $context,
            $this->checksum
        );
        $converted['id'] = (string) $this->mainMapping['entityUuid'];

        if (isset($data['children']) && \count($data['children']) > 0) {
            $this->setRelationMappings($data['children']);
        }

        $customerGroupMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $data['customer_group_id'],
            $context
        );

        if ($customerGroupMapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::CUSTOMER_GROUP,
                    $data['customer_group_id'],
                    DefaultEntities::SALES_CHANNEL
                )
            );

            return new ConvertStruct(null, $data);
        }
        $customerGroupUuid = $customerGroupMapping['entityUuid'];
        $this->mappingIds[] = $customerGroupMapping['id'];
        $converted['customerGroupId'] = $customerGroupUuid;

        $languageUuid = $this->mappingService->getLanguageUuid(
            $this->connectionId,
            $data['locale'],
            $context
        );

        if ($languageUuid === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::LANGUAGE,
                    $data['locale'],
                    DefaultEntities::SALES_CHANNEL
                )
            );

            return new ConvertStruct(null, $data);
        }

        $converted['languageId'] = $languageUuid;
        $converted['languages'] = $this->getSalesChannelLanguages($languageUuid, $data, $context);

        $this->filterExistingLanguageSalesChannelRelation($converted['id'], $converted['languages']);
        $this->filterDisabledPackLanguages($converted);

        if (empty($converted['languages'])) {
            unset($converted['languages']);
        }

        $currencyUuid = $this->mappingService->getCurrencyUuid(
            $this->connectionId,
            $data['currency'],
            $context
        );

        if ($currencyUuid === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::CURRENCY,
                    $data['currency'],
                    DefaultEntities::SALES_CHANNEL
                )
            );

            return new ConvertStruct(null, $data);
        }

        $converted['currencyId'] = $currencyUuid;
        $converted['currencies'] = [
            [
                'id' => $currencyUuid,
            ],
        ];

        $categoryMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $data['category_id'],
            $context
        );

        if ($categoryMapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::CATEGORY,
                    $data['category_id'],
                    DefaultEntities::SALES_CHANNEL
                )
            );

            return new ConvertStruct(null, $data);
        }
        $categoryUuid = $categoryMapping['entityUuid'];
        $this->mappingIds[] = $categoryMapping['id'];
        $converted['navigationCategoryId'] = $categoryUuid;

        $countryUuid = $this->getFirstActiveCountryId();
        $converted['countryId'] = $countryUuid;
        $converted['countries'] = [
            [
                'id' => $countryUuid,
            ],
        ];

        $paymentMethodUuid = $this->getFirstActivePaymentMethodId();
        $converted['paymentMethodId'] = $paymentMethodUuid;
        $converted['paymentMethods'] = [
            [
                'id' => $paymentMethodUuid,
            ],
        ];

        $shippingMethodUuid = $this->getFirstActiveShippingMethodId();
        $converted['shippingMethodId'] = $shippingMethodUuid;
        $converted['shippingMethods'] = [
            [
                'id' => $shippingMethodUuid,
            ],
        ];

        $converted['typeId'] = Defaults::SALES_CHANNEL_TYPE_STOREFRONT;
        $this->setSalesChannelTranslation($converted, $data);
        $this->convertValue($converted, 'name', $data, 'name');
        $converted['accessKey'] = AccessKeyHelper::generateAccessKey('sales-channel');

        unset(
            $data['id'],
            $data['main_id'],
            $data['title'],
            $data['position'],
            $data['host'],
            $data['base_path'],
            $data['base_url'],
            $data['hosts'],
            $data['secure'],
            $data['template_id'],
            $data['document_template_id'],
            $data['category_id'],
            $data['locale_id'],
            $data['currency_id'],
            $data['customer_group_id'],
            $data['fallback_id'],
            $data['customer_scope'],
            $data['default'],
            $data['active'],
            $data['locale'],
            $data['currency'],
            $data['_locale'],
            $data['children']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<mixed> $salesChannel
     * @param array<mixed> $data
     */
    protected function setSalesChannelTranslation(array &$salesChannel, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $salesChannel['translations'][$languageUuid] = $localeTranslation;
        }
    }

    protected function getFirstActiveShippingMethodId(): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true));

        $id = $this->shippingMethodRepo->searchIds($criteria, Context::createDefaultContext())->firstId() ?? '';

        if ($id === '') {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SHIPPING_METHOD,
                'default_shipping_method',
                $this->context
            );

            if ($mapping !== null) {
                $id = (string) $mapping['entityUuid'];
            }
        }

        return $id;
    }

    protected function getFirstActivePaymentMethodId(): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'));

        $id = $this->paymentRepository->searchIds($criteria, Context::createDefaultContext())->firstId() ?? '';

        if ($id === '') {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                PaymentMethodReader::getMappingName(),
                PaymentMethodReader::SOURCE_ID,
                $this->context
            );

            if ($mapping !== null) {
                $id = (string) $mapping['entityUuid'];
            }
        }

        return $id;
    }

    protected function getFirstActiveCountryId(): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'));

        return $this->countryRepository->searchIds($criteria, Context::createDefaultContext())->firstId() ?? '';
    }

    /**
     * @param array<mixed> $languageIds
     */
    protected function filterExistingLanguageSalesChannelRelation(string $salesChannelUuid, array &$languageIds): void
    {
        $insertLanguages = [];
        foreach ($languageIds as $languageId) {
            $criteria = (new Criteria())
                ->setLimit(1)
                ->addFilter(new EqualsFilter('id', $salesChannelUuid))
                ->addFilter(new EqualsFilter('languages.id', $languageId['id']));

            if ($this->salesChannelRepo->searchIds($criteria, Context::createDefaultContext())->getTotal() === 0) {
                $insertLanguages[] = $languageId;
            }
        }

        $languageIds = $insertLanguages;
    }

    /**
     * @param array<mixed> $converted
     */
    protected function filterDisabledPackLanguages(array &$converted): void
    {
        if ($this->languagePackRepo === null) {
            return;
        }

        $criteria = (new Criteria())
            ->addFilter(new EqualsAnyFilter('languageId', \array_column($converted['languages'], 'id')))
            ->addFilter(new EqualsFilter('salesChannelActive', false));

        $result = $this->languagePackRepo->search($criteria, Context::createDefaultContext());

        if ($result->getTotal() !== 0) {
            foreach ($result->getElements() as $packLanguage) {
                $packLanguageId = $packLanguage->getLanguageId();

                foreach ($converted['languages'] as &$language) {
                    if ($language['id'] === $packLanguageId) {
                        $language['id'] = Defaults::LANGUAGE_SYSTEM;
                    }
                }
                unset($language);

                if ($converted['languageId'] === $packLanguageId) {
                    $converted['languageId'] = Defaults::LANGUAGE_SYSTEM;
                }

                $this->loggingService->addLogEntry(
                    new DeactivatedPackLanguageLog($this->migrationContext->getRunUuid(), SalesChannelDataSet::getEntity(), $this->oldIdentifier, $packLanguageId)
                );
            }
        }
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    protected function getSalesChannelLanguages(string $languageUuid, array $data, Context $context): array
    {
        $languages = [];
        $languages[] = [
            'id' => $languageUuid,
        ];

        if (isset($data['children'])) {
            foreach ($data['children'] as $subShop) {
                $uuid = $this->mappingService->getLanguageUuid(
                    $this->connectionId,
                    $subShop['locale'],
                    $context
                );

                if ($uuid === null) {
                    continue;
                }

                $languages[] = [
                    'id' => $uuid,
                ];
            }
        }

        return $languages;
    }

    /**
     * @param array<mixed> $children
     */
    private function setRelationMappings(array $children): void
    {
        if (!isset($this->mainMapping['entityUuid'])) {
            return;
        }

        foreach ($children as $shop) {
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $shop['id'],
                $this->context,
                null,
                null,
                $this->mainMapping['entityUuid']
            );
            $this->mappingIds[] = $mapping['id'];
        }
    }
}
