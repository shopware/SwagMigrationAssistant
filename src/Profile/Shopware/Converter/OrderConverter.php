<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Exception\AssociationEntityRequiredMissingException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\UnknownEntityLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderDeliveryStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TransactionStateReader;

#[Package('services-settings')]
abstract class OrderConverter extends ShopwareConverter
{
    private const BILLING_ADDRESS = 'billing';

    private const SHIPPING_ADDRESS = 'shipping';

    protected string $mainLocale;

    protected Context $context;

    protected string $connectionId;

    protected string $connectionName;

    protected string $oldId;

    protected string $uuid;

    protected string $runId;

    /**
     * @var list<string>
     */
    protected array $requiredDataFieldKeys = [
        'customer',
        'currency',
        'currencyFactor',
        'payment',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected array $requiredAddressDataFieldKeys = [
        'firstname',
        'lastname',
        'zipcode',
        'city',
        'street',
        'salutation',
    ];

    protected int $paymentStatusId;

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected TaxCalculator $taxCalculator,
        private readonly EntityRepository $salesChannelRepository
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    /**
     * @throws AssociationEntityRequiredMissingException
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->generateChecksum($data);
        $this->oldId = $data['id'];
        $this->runId = $migrationContext->getRunUuid();
        $this->migrationContext = $migrationContext;

        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);
        if (empty($data['billingaddress']['id'])) {
            $fields[] = 'billingaddress';
        }
        if (isset($data['payment']) && empty($data['payment']['name'])) {
            $fields[] = 'paymentMethod';
        }

        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::ORDER,
                $this->oldId,
                \implode(',', $fields)
            ));

            return new ConvertStruct(null, $data);
        }

        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);
        $this->context = $context;

        $connection = $migrationContext->getConnection();
        $this->connectionName = '';
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
            $this->connectionName = $connection->getName();
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $converted['id'] = (string) $this->mainMapping['entityUuid'];
        unset($data['id']);
        $this->uuid = $converted['id'];

        if (isset($data['cleared'])) {
            $this->paymentStatusId = (int) $data['cleared'];
        }

        $this->convertValue($converted, 'orderNumber', $data, 'ordernumber');
        $this->convertValue($converted, 'customerComment', $data, 'customercomment');

        $customerMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            $data['userID'],
            $this->context
        );

        if ($customerMapping === null) {
            throw MigrationException::associationEntityRequiredMissing(
                DefaultEntities::ORDER,
                DefaultEntities::CUSTOMER
            );
        }

        $orderCustomerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_CUSTOMER,
            $this->oldId,
            $this->context
        );

        $converted['orderCustomer'] = [
            'id' => $orderCustomerMapping['entityUuid'],
            'customerId' => $customerMapping['entityUuid'],
        ];
        $this->mappingIds[] = $customerMapping['id'];
        $this->mappingIds[] = $orderCustomerMapping['id'];
        unset($customerMapping);

        $salutationUuid = $this->getSalutation($data['customer']['salutation']);
        if ($salutationUuid === null) {
            return new ConvertStruct(null, $data);
        }
        $converted['orderCustomer']['salutationId'] = $salutationUuid;

        $this->convertValue($converted['orderCustomer'], 'email', $data['customer'], 'email');
        $this->convertValue($converted['orderCustomer'], 'firstName', $data['customer'], 'firstname');
        $this->convertValue($converted['orderCustomer'], 'lastName', $data['customer'], 'lastname');
        $this->convertValue($converted['orderCustomer'], 'customerNumber', $data['customer'], 'customernumber');
        unset($data['userID'], $data['customer']);

        $this->convertValue($converted, 'currencyFactor', $data, 'currencyFactor', self::TYPE_FLOAT);

        $currencyUuid = null;
        if (isset($data['currency'])) {
            $currencyUuid = $this->mappingService->getCurrencyUuid(
                $this->connectionId,
                $data['currency'],
                $this->context
            );
        }
        if ($currencyUuid === null) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::ORDER,
                $this->oldId,
                'currency'
            ));

            return new ConvertStruct(null, $data);
        }

        $converted['currencyId'] = $currencyUuid;

        $converted['itemRounding'] = [
            'decimals' => $context->getRounding()->getDecimals(),
            'interval' => 0.01,
            'roundForNet' => true,
        ];
        $converted['totalRounding'] = $converted['itemRounding'];

        $this->convertValue($converted, 'orderDateTime', $data, 'ordertime', self::TYPE_DATETIME);

        $stateMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            (string) $data['status'],
            $this->context
        );

        if ($stateMapping === null) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                'order_state',
                (string) $data['status'],
                DefaultEntities::ORDER,
                $this->oldId
            ));

            return new ConvertStruct(null, $data);
        }
        $converted['stateId'] = $stateMapping['entityUuid'];
        $this->mappingIds[] = $stateMapping['id'];

        $calculatedTax = new CalculatedTax(
            (float) $data['invoice_shipping'] - $data['invoice_shipping_net'],
            (float) $data['invoice_shipping_tax_rate'],
            (float) $data['invoice_shipping']
        );

        $shippingCosts = new CalculatedPrice(
            (float) $data['invoice_shipping'],
            (float) $data['invoice_shipping'],
            new CalculatedTaxCollection([$calculatedTax]),
            new TaxRuleCollection([new TaxRule((float) $data['invoice_shipping_tax_rate'])])
        );

        if (isset($data['details'])) {
            $taxStatus = $this->getTaxStatus($data);
            $taxRules = $this->getTaxRules($data, $taxStatus);

            $converted['lineItems'] = $this->getLineItems($data['details'], $taxRules, $taxStatus);

            $converted['price'] = new CartPrice(
                (float) $data['invoice_amount_net'],
                (float) $data['invoice_amount'],
                (float) $data['invoice_amount'] - (float) $data['invoice_shipping'],
                new CalculatedTaxCollection([]),
                $taxRules,
                $taxStatus
            );

            $converted['shippingCosts'] = $shippingCosts;
        }
        unset(
            $data['net'],
            $data['taxfree'],
            $data['invoice_amount_net'],
            $data['invoice_amount'],
            $data['invoice_shipping_net'],
            $data['invoice_shipping'],
            $data['details'],
            $data['currency']
        );

        $converted['deliveries'] = $this->getDeliveries($data, $converted, $shippingCosts);
        if (isset($data['shippingaddress']['ustid']) && $data['shippingaddress']['ustid'] !== '') {
            $converted['orderCustomer']['vatIds'][] = $data['shippingaddress']['ustid'];
        }
        unset(
            $data['trackingcode'],
            $data['shippingMethod'],
            $data['dispatchID'],
            $data['shippingaddress'],
            $data['status'],
            $data['orderstatus']
        );

        $this->applyTransactions($data, $converted);
        unset($data['cleared'], $data['paymentstatus']);

        $billingAddress = $this->getAddress($data['billingaddress']);
        if (empty($billingAddress)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::ORDER,
                $this->oldId,
                'billingaddress'
            ));

            return new ConvertStruct(null, $data);
        }
        if (isset($data['billingaddress']['ustid']) && $data['billingaddress']['ustid'] !== '') {
            $converted['orderCustomer']['vatIds'][] = $data['billingaddress']['ustid'];
        }
        $converted['billingAddressId'] = $billingAddress['id'];
        $converted['addresses'][] = $billingAddress;
        unset($data['billingaddress']);

        if (isset($data['subshopID'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $data['subshopID'],
                $this->context
            );

            if ($mapping !== null) {
                $converted['salesChannelId'] = $mapping['entityUuid'];
                $this->mappingIds[] = $mapping['id'];
                unset($data['subshopID']);
            }
        }

        if (empty($converted['salesChannelId'])) {
            $criteria = new Criteria();
            $criteria->setLimit(1);
            $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
            $converted['salesChannelId'] = $this->salesChannelRepository->searchIds($criteria, $context)->firstId();
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::ORDER, $this->connectionName, ['id', 'orderID'], $this->context);
        }
        unset($data['attributes']);

        if (isset($data['locale'])) {
            $languageMapping = $this->mappingService->getLanguageUuid(
                $this->connectionId,
                $data['locale'],
                $this->context
            );

            if ($languageMapping !== null) {
                $converted['languageId'] = $languageMapping;
            }
        }
        unset($data['locale']);

        $converted['deepLinkCode'] = \md5($converted['id']);

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['invoice_shipping_tax_rate'],
            $data['transactionID'],
            $data['comment'],
            $data['internalcomment'],
            $data['partnerID'],
            $data['temporaryID'],
            $data['referer'],
            $data['cleareddate'],
            $data['remote_addr'],
            $data['deviceType'],
            $data['is_proportional_calculation'],
            $data['changed'],
            $data['payment'],
            $data['paymentID'],
            $data['language'],
            $data['documents']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    protected function applyTransactions(array $data, array &$converted): void
    {
        $converted['transactions'] = [];
        if (!isset($converted['lineItems'])) {
            return;
        }

        $cartPrice = $converted['price'];
        if (!$cartPrice instanceof CartPrice) {
            return;
        }

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            TransactionStateReader::getMappingName(),
            $data['cleared'],
            $this->context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                'transaction_state',
                $data['cleared'],
                DefaultEntities::ORDER_TRANSACTION,
                $this->oldId
            ));

            return;
        }
        $stateId = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_TRANSACTION,
            $this->oldId,
            $this->context
        );

        $paymentMethodUuid = $this->getPaymentMethod($data);

        if ($paymentMethodUuid === null) {
            return;
        }
        $id = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $transactions = [
            [
                'id' => $id,
                'paymentMethodId' => $paymentMethodUuid,
                'stateId' => $stateId,
                'amount' => new CalculatedPrice(
                    $cartPrice->getTotalPrice(),
                    $cartPrice->getTotalPrice(),
                    $cartPrice->getCalculatedTaxes(),
                    $cartPrice->getTaxRules()
                ),
            ],
        ];

        $converted['transactions'] = $transactions;
    }

    /**
     * @param array<string, mixed> $originalData
     */
    protected function getPaymentMethod(array $originalData): ?string
    {
        $paymentMethodMapping = $this->mappingService->getMapping(
            $this->connectionId,
            PaymentMethodReader::getMappingName(),
            $originalData['payment']['id'],
            $this->context
        );

        if ($paymentMethodMapping === null) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                'payment_method',
                $originalData['payment']['id'],
                DefaultEntities::ORDER_TRANSACTION,
                $this->oldId
            ));

            return null;
        }

        $this->mappingIds[] = $paymentMethodMapping['id'];

        return $paymentMethodMapping['entityUuid'];
    }

    /**
     * @param array<string, mixed> $originalData
     *
     * @return array<string, mixed>
     */
    protected function getAddress(array $originalData, string $type = self::BILLING_ADDRESS): array
    {
        $fields = $this->checkForEmptyRequiredDataFields($originalData, $this->requiredAddressDataFieldKeys);
        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::ORDER_ADDRESS,
                $originalData['id'],
                \implode(',', $fields)
            ));

            return [];
        }

        $entityName = DefaultEntities::ORDER_ADDRESS;
        if ($type !== self::BILLING_ADDRESS) {
            $entityName = DefaultEntities::ORDER_ADDRESS . '_' . $type;
        }

        $address = [];
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            $entityName,
            $originalData['id'],
            $this->context
        );
        $address['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY,
            $originalData['countryID'],
            $this->context
        );
        $address['countryId'] = $mapping['entityUuid'] ?? null;
        if ($mapping !== null) {
            $this->mappingIds[] = $mapping['id'];
        }

        if (isset($originalData['country']) && $address['countryId'] === null) {
            $address['country'] = $this->getCountry($originalData['country']);
        }

        if (isset($originalData['stateID'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::COUNTRY_STATE,
                $originalData['stateID'],
                $this->context
            );
            $address['countryStateId'] = $mapping['entityUuid'] ?? null;
            if ($mapping !== null) {
                $this->mappingIds[] = $mapping['id'];
            }

            if (isset($address['countryStateId'], $originalData['state']) && ($address['countryId'] !== null || isset($address['country']['id']))) {
                $countryId = $address['country']['id'] ?? null;
                if ($countryId === null && isset($address['countryId'])) {
                    $countryId = $address['countryId'];
                }

                if ($countryId !== null) {
                    $address['countryState'] = $this->getCountryState($originalData['state'], $countryId);
                }
            }
        }

        $salutationUuid = $this->getSalutation($originalData['salutation']);
        if ($salutationUuid === null) {
            return [];
        }
        $address['salutationId'] = $salutationUuid;

        $this->convertValue($address, 'firstName', $originalData, 'firstname');
        $this->convertValue($address, 'lastName', $originalData, 'lastname');
        $this->convertValue($address, 'zipcode', $originalData, 'zipcode');
        $this->convertValue($address, 'city', $originalData, 'city');
        $this->convertValue($address, 'company', $originalData, 'company');
        $this->convertValue($address, 'street', $originalData, 'street');
        $this->convertValue($address, 'department', $originalData, 'department');
        $this->convertValue($address, 'title', $originalData, 'title');
        $this->convertValue($address, 'phoneNumber', $originalData, 'phone');
        $this->convertValue($address, 'additionalAddressLine1', $originalData, 'additional_address_line1');
        $this->convertValue($address, 'additionalAddressLine2', $originalData, 'additional_address_line2');

        return $address;
    }

    /**
     * @param array<string, mixed> $oldCountryData
     *
     * @return array<string, mixed>
     */
    protected function getCountry(array $oldCountryData): array
    {
        $country = [];
        if (isset($oldCountryData['countryiso'], $oldCountryData['iso3'])) {
            $country['id'] = $this->mappingService->getCountryUuid(
                $oldCountryData['id'],
                $oldCountryData['countryiso'],
                $oldCountryData['iso3'],
                $this->connectionId,
                $this->context
            );
        }

        if (!isset($country['id'])) {
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::COUNTRY,
                $oldCountryData['id'],
                $this->context
            );
            $country['id'] = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
        }

        $this->applyCountryTranslation($country, $oldCountryData);
        $this->convertValue($country, 'iso', $oldCountryData, 'countryiso');
        $this->convertValue($country, 'position', $oldCountryData, 'position', self::TYPE_INTEGER);
        $this->convertValue($country, 'taxFree', $oldCountryData, 'taxfree', self::TYPE_BOOLEAN);
        $this->convertValue($country, 'taxfreeForVatId', $oldCountryData, 'taxfree_ustid', self::TYPE_BOOLEAN);
        $this->convertValue($country, 'taxfreeVatidChecked', $oldCountryData, 'taxfree_ustid_checked', self::TYPE_BOOLEAN);
        $this->convertValue($country, 'active', $oldCountryData, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($country, 'iso3', $oldCountryData, 'iso3');
        $this->convertValue($country, 'displayStateInRegistration', $oldCountryData, 'display_state_in_registration', self::TYPE_BOOLEAN);
        $this->convertValue($country, 'forceStateInRegistration', $oldCountryData, 'force_state_in_registration', self::TYPE_BOOLEAN);
        $this->convertValue($country, 'name', $oldCountryData, 'countryname');

        return $country;
    }

    /**
     * @param array<string, mixed> $country
     * @param array<string, mixed> $data
     */
    protected function applyCountryTranslation(array &$country, array $data): void
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
        $localeTranslation['countryId'] = $country['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'countryname');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $country['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<string, mixed> $oldStateData
     *
     * @return array<string, mixed>
     */
    protected function getCountryState(array $oldStateData, string $newCountryId): array
    {
        $state = [];
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE,
            $oldStateData['id'],
            $this->context
        );
        $state['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $state['countryId'] = $newCountryId;

        $this->applyCountryStateTranslation($state, $oldStateData);
        $this->convertValue($state, 'shortCode', $oldStateData, 'shortcode');
        $this->convertValue($state, 'position', $oldStateData, 'position', self::TYPE_INTEGER);
        $this->convertValue($state, 'active', $oldStateData, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($state, 'name', $oldStateData, 'name');

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $data
     */
    protected function applyCountryStateTranslation(array &$state, array $data): void
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
        $translation = [];
        $translation['countryStateId'] = $state['id'];

        $this->convertValue($translation, 'name', $data, 'name');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $state['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDeliveries(array $data, array $converted, CalculatedPrice $shippingCosts): array
    {
        $deliveries = [];
        $deliveryStateMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderDeliveryStateReader::getMappingName(),
            (string) $data['status'],
            $this->context
        );

        if ($deliveryStateMapping === null) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                'order_delivery_state',
                (string) $data['status'],
                DefaultEntities::ORDER,
                $this->oldId
            ));

            return [];
        }
        $this->mappingIds[] = $deliveryStateMapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_DELIVERY,
            $this->oldId,
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];

        $delivery = [
            'id' => $mapping['entityUuid'],
            'stateId' => $deliveryStateMapping['entityUuid'],
            'shippingDateEarliest' => $converted['orderDateTime'],
            'shippingDateLatest' => $converted['orderDateTime'],
        ];

        if (isset($data['dispatchID'])) {
            $delivery['shippingMethodId'] = $this->getShippingMethod($data['dispatchID']);
        }

        if (!isset($delivery['shippingMethodId'])) {
            return [];
        }

        if (isset($data['shippingaddress']['id'])) {
            $delivery['shippingOrderAddress'] = $this->getAddress($data['shippingaddress'], self::SHIPPING_ADDRESS);
        }

        if (!isset($delivery['shippingOrderAddress'])) {
            $delivery['shippingOrderAddress'] = $this->getAddress($data['billingaddress']);
        }

        if (isset($data['trackingcode']) && $data['trackingcode'] !== '') {
            $delivery['trackingCodes'] = [$data['trackingcode']];
        }

        if (isset($converted['lineItems'])) {
            $positions = [];
            foreach ($converted['lineItems'] as $lineItem) {
                $mapping = $this->mappingService->getOrCreateMapping(
                    $this->connectionId,
                    DefaultEntities::ORDER_DELIVERY_POSITION,
                    $lineItem['id'],
                    $this->context
                );
                $this->mappingIds[] = $mapping['id'];
                $positions[] = [
                    'id' => $mapping['entityUuid'],
                    'orderLineItemId' => $lineItem['id'],
                    'price' => $lineItem['price'],
                ];
            }

            $delivery['positions'] = $positions;
        }
        $delivery['shippingCosts'] = $shippingCosts;

        $deliveries[] = $delivery;

        return $deliveries;
    }

    protected function getShippingMethod(string $shippingMethodId): ?string
    {
        $shippingMethodMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SHIPPING_METHOD,
            $shippingMethodId,
            $this->context
        );

        if ($shippingMethodMapping === null) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                DefaultEntities::SHIPPING_METHOD,
                $shippingMethodId,
                DefaultEntities::ORDER,
                $this->oldId
            ));

            return null;
        }
        $this->mappingIds[] = $shippingMethodMapping['id'];

        return $shippingMethodMapping['entityUuid'];
    }

    /**
     * @param array<string, mixed> $originalData
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getLineItems(array $originalData, TaxRuleCollection $taxRules, string $taxStatus): array
    {
        $lineItems = [];

        foreach ($originalData as $originalLineItem) {
            $isProduct = (int) $originalLineItem['modus'] === 0 && (int) $originalLineItem['articleID'] !== 0;

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::ORDER_LINE_ITEM,
                $originalLineItem['id'],
                $this->context
            );
            $this->mappingIds[] = $mapping['id'];

            $lineItem = [
                'id' => $mapping['entityUuid'],
                'identifier' => $mapping['entityUuid'],
            ];

            if ($isProduct) {
                if ($originalLineItem['articleordernumber'] !== null) {
                    $mapping = $this->mappingService->getMapping(
                        $this->connectionId,
                        DefaultEntities::PRODUCT,
                        $originalLineItem['articleordernumber'],
                        $this->context
                    );

                    if ($mapping !== null) {
                        $lineItem['referencedId'] = $mapping['entityUuid'];
                        $lineItem['productId'] = $mapping['entityUuid'];
                        $lineItem['payload']['productNumber'] = $originalLineItem['articleordernumber'] ?? '';
                        $this->mappingIds[] = $mapping['id'];
                    }
                }

                $lineItem['type'] = LineItem::PRODUCT_LINE_ITEM_TYPE;
            } else {
                if ($originalLineItem['price'] < 0) {
                    $lineItem['type'] = LineItem::CREDIT_LINE_ITEM_TYPE;
                } else {
                    $lineItem['type'] = LineItem::CUSTOM_LINE_ITEM_TYPE;
                }
            }
            $lineItem['payload']['options'] = [];

            $this->convertValue($lineItem, 'quantity', $originalLineItem, 'quantity', self::TYPE_INTEGER);
            $this->convertValue($lineItem, 'label', $originalLineItem, 'name');

            $calculatedTax = null;
            $totalPrice = $lineItem['quantity'] * $originalLineItem['price'];
            if ($taxStatus === CartPrice::TAX_STATE_NET) {
                $calculatedTax = $this->taxCalculator->calculateNetTaxes($totalPrice, $taxRules);
            }

            if ($taxStatus === CartPrice::TAX_STATE_GROSS) {
                $calculatedTax = $this->taxCalculator->calculateGrossTaxes($totalPrice, $taxRules);
            }

            if ($taxStatus === CartPrice::TAX_STATE_FREE) {
                $calculatedTax = new CalculatedTaxCollection([
                    new CalculatedTax(0, 0, 0),
                ]);
            }

            if ($calculatedTax !== null) {
                $lineItem['price'] = new CalculatedPrice(
                    (float) $originalLineItem['price'],
                    (float) $totalPrice,
                    $calculatedTax,
                    $taxRules,
                    (int) $lineItem['quantity']
                );

                $lineItem['priceDefinition'] = new QuantityPriceDefinition(
                    (float) $originalLineItem['price'],
                    $taxRules,
                    $lineItem['quantity'] ?? 1
                );

                if ($lineItem['type'] === LineItem::CREDIT_LINE_ITEM_TYPE) {
                    $lineItem['priceDefinition'] = new AbsolutePriceDefinition(
                        (float) $originalLineItem['price']
                    );
                }
            }

            if (isset($originalLineItem['esd'])) {
                $download = $this->getOrderLineItemDownload($originalLineItem['esd']);

                if ($download !== null) {
                    $lineItem['downloads'][] = $download;
                }
            }

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * @param array<string, mixed> $originalData
     */
    protected function getTaxRules(array $originalData, string $taxStatus): TaxRuleCollection
    {
        if ($taxStatus === CartPrice::TAX_STATE_FREE) {
            return new TaxRuleCollection([new TaxRule(0.0)]);
        }

        $taxRates = \array_unique(\array_column($originalData['details'], 'tax_rate'));

        $taxRules = [];
        foreach ($taxRates as $taxRate) {
            $taxRules[] = new TaxRule((float) $taxRate);
        }

        return new TaxRuleCollection($taxRules);
    }

    /**
     * @param array<string, mixed> $originalData
     */
    protected function getTaxStatus(array $originalData): string
    {
        $taxStatus = CartPrice::TAX_STATE_GROSS;
        if (isset($originalData['net']) && (bool) $originalData['net']) {
            $taxStatus = CartPrice::TAX_STATE_NET;
        }
        if (isset($originalData['taxfree']) && (bool) $originalData['taxfree']) {
            $taxStatus = CartPrice::TAX_STATE_FREE;
        }

        return $taxStatus;
    }

    protected function getSalutation(string $salutation): ?string
    {
        $salutationMapping = $this->mappingService->getMapping(
            $this->connectionId,
            SalutationReader::getMappingName(),
            $salutation,
            $this->context
        );

        if ($salutationMapping === null) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                'salutation',
                $salutation,
                DefaultEntities::ORDER,
                $this->oldId
            ));

            return null;
        }

        $this->mappingIds[] = $salutationMapping['id'];

        return $salutationMapping['entityUuid'];
    }

    /**
     * @param array<string, mixed> $originalEsdItem
     *
     * @return array<string, mixed>|null
     */
    private function getOrderLineItemDownload(array $originalEsdItem): ?array
    {
        $mediaMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            'esd_' . $originalEsdItem['esdID'],
            $this->context
        );

        if (!\is_array($mediaMapping)) {
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                'product_download_media',
                $originalEsdItem['esdID'],
                DefaultEntities::ORDER,
                $this->oldId
            ));

            return null;
        }
        $this->mappingIds[] = $mediaMapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::ORDER_LINE_ITEM_DOWNLOAD,
            $originalEsdItem['id'],
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];

        $accessGranted = false;
        if (isset($originalEsdItem['downloadAvailablePaymentStatus'])) {
            $paymentStatusArray = \unserialize($originalEsdItem['downloadAvailablePaymentStatus'], ['allowed_classes' => false]);

            if (\is_array($paymentStatusArray) && isset($this->paymentStatusId)) {
                $accessGranted = \in_array($this->paymentStatusId, $paymentStatusArray, true);
            }
        }

        return [
            'id' => $mapping['entityUuid'],
            'mediaId' => $mediaMapping['entityUuid'],
            'accessGranted' => $accessGranted,
            'position' => 0,
        ];
    }
}
