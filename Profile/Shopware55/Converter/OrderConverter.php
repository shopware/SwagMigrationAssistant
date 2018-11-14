<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Cart\Price\Struct\Price;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\DiscountSurcharge\Cart\DiscountSurchargeCollector;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodTranslation\ShippingMethodTranslationDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\Country\Aggregate\CountryStateTranslation\CountryStateTranslationDefinition;
use Shopware\Core\System\Country\Aggregate\CountryTranslation\CountryTranslationDefinition;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\Aggregate\CurrencyTranslation\CurrencyTranslationDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Logging\LoggingType;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;

class OrderConverter implements ConverterInterface
{
    /**
     * @var Shopware55MappingService
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var string
     */
    private $mainLocale;

    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $profileId;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string
     */
    private $oldId;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string[]
     */
    private $requiredDataFieldKeys = [
        'customer',
        'currencyFactor',
        'payment',
        'paymentcurrency',
        'status',
    ];

    public function __construct(
        Shopware55MappingService $mappingService,
        ConverterHelperService $converterHelperService,
        TaxCalculator $taxCalculator,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->taxCalculator = $taxCalculator;
        $this->loggingService = $loggingService;
    }

    public function supports(): string
    {
        return OrderDefinition::getEntityName();
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    /**
     * @throws AssociationEntityRequiredMissingException
     */
    public function convert(
        array $data,
        Context $context,
        string $runId,
        string $profileId,
        ?string $catalogId = null,
        ?string $salesChannelId = null
    ): ConvertStruct {
        $this->oldId = $data['id'];
        $this->runId = $runId;

        $fields = $this->helper->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);
        if (!isset($data['billingaddress']['id'])) {
            $fields[] = 'billingaddress';
        }

        if (!empty($fields)) {
            $this->loggingService->addWarning(
                $this->runId,
                LoggingType::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data',
                sprintf('Order-Entity could not converted cause of empty necessary field(s): %s.', implode(', ', $fields)),
                [
                    'id' => $this->oldId,
                    'entity' => 'Order',
                    'fields' => $fields,
                ]
            );

            return new ConvertStruct(null, $data);
        }

        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);
        $this->context = $context;
        $this->profileId = $profileId;

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            OrderDefinition::getEntityName(),
            $data['id'],
            $this->context
        );
        unset($data['id']);
        $this->uuid = $converted['id'];

        $customerId = $this->mappingService->getUuid(
            $this->profileId,
            CustomerDefinition::getEntityName(),
            $data['customer']['email'],
            $this->context
        );

        if ($customerId === null) {
            $customerId = $this->mappingService->getUuid(
                $this->profileId,
                CustomerDefinition::getEntityName(),
                $data['userID'],
                $this->context
            );
        }

        if ($customerId === null) {
            throw new AssociationEntityRequiredMissingException(
                OrderDefinition::getEntityName(),
                CustomerDefinition::getEntityName()
            );
        }

        $converted['orderCustomer'] = [
            'customerId' => $customerId,
        ];

        $this->helper->convertValue($converted['orderCustomer'], 'email', $data['customer'], 'email');
        $this->helper->convertValue($converted['orderCustomer'], 'firstName', $data['customer'], 'firstname');
        $this->helper->convertValue($converted['orderCustomer'], 'lastName', $data['customer'], 'lastname');
        $this->helper->convertValue($converted['orderCustomer'], 'salutation', $data['customer'], 'salutation');
        $this->helper->convertValue($converted['orderCustomer'], 'customerNumber', $data['customer'], 'customernumber');
        unset($data['userID'], $data['customer']);

        $this->helper->convertValue($converted, 'isNet', $data, 'net', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'isTaxFree', $data, 'taxfree', $this->helper::TYPE_BOOLEAN);

        if ($converted['isNet']) {
            $amount = (float) $data['invoice_amount_net'];
            unset($data['invoice_amount_net'], $data['invoice_amount']);
            $shipping = (float) $data['invoice_shipping_net'];
            unset($data['invoice_shipping_net'], $data['invoice_shipping']);
        } else {
            $amount = (float) $data['invoice_amount'];
            unset($data['invoice_amount'], $data['invoice_amount_net']);
            $shipping = (float) $data['invoice_shipping'];
            unset($data['invoice_shipping'], $data['invoice_shipping_net']);
        }
        $converted['amountTotal'] = $amount;
        $converted['shippingTotal'] = $shipping;
        $converted['positionPrice'] = $amount - $shipping;

        $this->helper->convertValue($converted, 'currencyFactor', $data, 'currencyFactor', $this->helper::TYPE_FLOAT);
        $converted['currency'] = $this->getCurrency($data['paymentcurrency']);
        unset($data['currency'], $data['currencyFactor'], $data['paymentcurrency']);

        $this->getPaymentMethod($data, $converted);
        unset($data['payment'], $data['paymentID']);

        $this->helper->convertValue($converted, 'date', $data, 'ordertime', $this->helper::TYPE_DATETIME);

        $converted['stateId'] = $this->mappingService->getOrderStateUuid((int) $data['status'], $this->context);
        if (!isset($converted['stateId'])) {
            $this->loggingService->addWarning(
                $this->runId,
                LoggingType::UNKNOWN_ORDER_STATE,
                'Cannot find order state',
                'Order-Entity could not converted cause of unknown order state',
                [
                    'id' => $this->oldId,
                    'orderState' => $data['status'],
                ]
            );

            return new ConvertStruct(null, $data);
        }
        unset($data['status'], $data['orderstatus']);

        $converted['deliveries'] = $this->getDeliveries($data, $converted);
        unset($data['trackingcode'], $data['shippingMethod'], $data['dispatchID'], $data['shippingaddress']);

        $converted['billingAddress'] = $this->getAddress($data['billingaddress']);
        unset($data['billingaddress']);

        if (isset($data['details'])) {
            $converted['lineItems'] = $this->getLineItems($data['details']);
        }
        unset($data['details']);

        $this->getTransactions($data, $converted);
        unset($data['cleared'], $data['paymentstatus']);

        $converted['salesChannelId'] = $salesChannelId;

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['invoice_shipping_tax_rate'],
            $data['transactionID'],
            $data['comment'],
            $data['customercomment'],
            $data['internalcomment'],
            $data['partnerID'],
            $data['temporaryID'],
            $data['referer'],
            $data['cleareddate'],
            $data['remote_addr'],
            $data['deviceType'],
            $data['is_proportional_calculation'],
            $data['changed'],

            // TODO check how to handle these
            $data['ordernumber'],
            $data['language'], // TODO use for sales channel information?
            $data['subshopID'], // TODO use for sales channel information?
            $data['attributes'],
            $data['documents']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function getCurrency(array $originalData): array
    {
        $currency = [];
        $currency['id'] = $this->mappingService->getCurrencyUuid($originalData['currency'], $this->context);

        if (!isset($currency['id'])) {
            $currency['id'] = $this->mappingService->createNewUuid(
                $this->profileId,
                CurrencyDefinition::getEntityName(),
                $originalData['id'],
                $this->context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            CurrencyTranslationDefinition::getEntityName(),
            $originalData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $this->helper->convertValue($currency, 'isDefault', $originalData, 'standard', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($currency, 'factor', $originalData, 'factor', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($currency, 'position', $originalData, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($translation, 'shortName', $originalData, 'currency');
        $this->helper->convertValue($translation, 'name', $originalData, 'name');

        $currency['symbol'] = html_entity_decode($originalData['templatechar']);
        $currency['placedInFront'] = ((int) $originalData['symbol_position']) > 16;

        $languageData = $this->mappingService->getLanguageUuid($this->profileId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $currency['translations'][$languageData['uuid']] = $translation;

        return $currency;
    }

    private function getTransactions(array $data, array &$converted): void
    {
        if (!isset($converted['lineItems'])) {
            return;
        }

        $taxRates = array_unique(array_column($converted['lineItems'], 'taxRate'));

        $taxRules = [];
        foreach ($taxRates as $taxRate) {
            $taxRules[] = new TaxRule($taxRate);
        }

        $taxRules = new TaxRuleCollection($taxRules);

        if ($converted['isNet']) {
            $calculatedTaxes = $this->taxCalculator->calculateNetTaxes($converted['amountTotal'], $taxRules);
        } else {
            $calculatedTaxes = $this->taxCalculator->calculateGrossTaxes($converted['amountTotal'], $taxRules);
        }

        $transactions = [
            [
                'id' => Uuid::uuid4()->getHex(),
                'paymentMethodId' => $converted['paymentMethod']['id'],
                'orderTransactionStateId' => $this->mappingService->getTransactionStateUuid((int) $data['cleared'], $this->context),
                'amount' => new Price(
                    $converted['amountTotal'],
                    $converted['amountTotal'],
                    $calculatedTaxes,
                    $taxRules
                ),
            ],
        ];

        $converted['transactions'] = $transactions;
    }

    private function getPaymentMethod(array $originalData, array &$converted): void
    {
        $paymentMethodUuid = $this->mappingService->getPaymentUuid($originalData['payment']['name'], $this->context);

        if ($paymentMethodUuid !== null) {
            $paymentMethod['id'] = $paymentMethodUuid;
        } else {
            $paymentMethod['id'] = $this->mappingService->createNewUuid(
                $this->profileId,
                PaymentMethodDefinition::getEntityName(),
                $originalData['payment']['id'],
                $this->context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            PaymentMethodTranslationDefinition::getEntityName(),
            $originalData['payment']['id'] . ':' . $this->mainLocale,
            $this->context
        );

        // TODO: Delete this default value, if the Core deletes the require Flag of the PaymentMethodTranslation
        if ($originalData['payment']['additionaldescription'] === '') {
            $originalData['payment']['additionaldescription'] = '....';
        }

        $translation['paymentMethodId'] = $paymentMethod['id'];
        $this->helper->convertValue($translation, 'name', $originalData['payment'], 'description');
        $this->helper->convertValue($translation, 'additionalDescription', $originalData['payment'], 'additionaldescription');

        //todo: What about the PluginID?
        $this->helper->convertValue($paymentMethod, 'technicalName', $originalData['payment'], 'name');
        $this->helper->convertValue($paymentMethod, 'template', $originalData['payment'], 'template');
        $this->helper->convertValue($paymentMethod, 'class', $originalData['payment'], 'class');
        $this->helper->convertValue($paymentMethod, 'table', $originalData['payment'], 'table');
        $this->helper->convertValue($paymentMethod, 'hide', $originalData['payment'], 'hide', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($paymentMethod, 'percentageSurcharge', $originalData['payment'], 'debit_percent', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($paymentMethod, 'absoluteSurcharge', $originalData['payment'], 'surcharge', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($paymentMethod, 'surchargeString', $originalData['payment'], 'surchargestring');
        $this->helper->convertValue($paymentMethod, 'position', $originalData['payment'], 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($paymentMethod, 'active', $originalData['payment'], 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($paymentMethod, 'allowEsd', $originalData['payment'], 'esdactive', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($paymentMethod, 'usedIframe', $originalData['payment'], 'embediframe');
        $this->helper->convertValue($paymentMethod, 'hideProspect', $originalData['payment'], 'hideprospect', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($paymentMethod, 'action', $originalData['payment'], 'action');
        $this->helper->convertValue($paymentMethod, 'source', $originalData['payment'], 'source', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($paymentMethod, 'mobileInactive', $originalData['payment'], 'mobile_inactive', $this->helper::TYPE_BOOLEAN);

        $languageData = $this->mappingService->getLanguageUuid($this->profileId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $paymentMethod['translations'][$languageData['uuid']] = $translation;

        $converted['paymentMethod'] = $paymentMethod;
    }

    private function getAddress(array $originalData): array
    {
        $address = [];
        $address['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            OrderAddressDefinition::getEntityName(),
            $originalData['id'],
            $this->context
        );

        $address['countryId'] = $this->mappingService->getUuid(
            $this->profileId,
            CountryDefinition::getEntityName(),
            $originalData['countryID'],
            $this->context
        );

        if (isset($originalData['country']) && $address['countryId'] === null) {
            $address['country'] = $this->getCountry($originalData['country']);
        }

        if (isset($originalData['stateID'])) {
            $address['countryStateId'] = $this->mappingService->getUuid(
                $this->profileId,
                CountryStateDefinition::getEntityName(),
                $originalData['stateID'],
                $this->context
            );

            if (isset($address['countryStateId'], $originalData['state']) && ($address['countryId'] !== null || isset($address['country']['id']))) {
                $address['countryState'] = $this->getCountryState($originalData['state'], $address['countryId'] ?? $address['country']['id']);
            }
        }

        $this->helper->convertValue($address, 'salutation', $originalData, 'salutation');
        $this->helper->convertValue($address, 'firstName', $originalData, 'firstname');
        $this->helper->convertValue($address, 'lastName', $originalData, 'lastname');
        $this->helper->convertValue($address, 'zipcode', $originalData, 'zipcode');
        $this->helper->convertValue($address, 'city', $originalData, 'city');
        $this->helper->convertValue($address, 'company', $originalData, 'company');
        $this->helper->convertValue($address, 'street', $originalData, 'street');
        $this->helper->convertValue($address, 'department', $originalData, 'department');
        $this->helper->convertValue($address, 'title', $originalData, 'title');
        if (isset($originalData['ustid'])) {
            $this->helper->convertValue($address, 'vatId', $originalData, 'ustid');
        }
        $this->helper->convertValue($address, 'phoneNumber', $originalData, 'phone');
        $this->helper->convertValue($address, 'additionalAddressLine1', $originalData, 'additional_address_line1');
        $this->helper->convertValue($address, 'additionalAddressLine2', $originalData, 'additional_address_line2');

        return $address;
    }

    private function getCountry(array $oldCountryData): array
    {
        $country = [];
        if (isset($oldCountryData['countryiso'], $oldCountryData['iso3'])) {
            $country['id'] = $this->mappingService->getCountryUuid(
                $oldCountryData['id'],
                $oldCountryData['countryiso'],
                $oldCountryData['iso3'],
                $this->profileId,
                $this->context
            );
        }

        if (!isset($country['id'])) {
            $country['id'] = $this->mappingService->createNewUuid(
                $this->profileId,
                CountryDefinition::getEntityName(),
                $oldCountryData['id'],
                $this->context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            CountryTranslationDefinition::getEntityName(),
            $oldCountryData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['countryId'] = $country['id'];
        $this->helper->convertValue($translation, 'name', $oldCountryData, 'countryname');

        $this->helper->convertValue($country, 'iso', $oldCountryData, 'countryiso');
        $this->helper->convertValue($country, 'position', $oldCountryData, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($country, 'taxFree', $oldCountryData, 'taxfree', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'taxfreeForVatId', $oldCountryData, 'taxfree_ustid', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'taxfreeVatidChecked', $oldCountryData, 'taxfree_ustid_checked', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'active', $oldCountryData, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'iso3', $oldCountryData, 'iso3');
        $this->helper->convertValue($country, 'displayStateInRegistration', $oldCountryData, 'display_state_in_registration', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($country, 'forceStateInRegistration', $oldCountryData, 'force_state_in_registration', $this->helper::TYPE_BOOLEAN);

        $languageData = $this->mappingService->getLanguageUuid($this->profileId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $country['translations'][$languageData['uuid']] = $translation;

        return $country;
    }

    private function getCountryState(array $oldStateData, string $newCountryId): array
    {
        $state = [];
        $state['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            CountryStateDefinition::getEntityName(),
            $oldStateData['id'],
            $this->context
        );
        $state['countryId'] = $newCountryId;

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            CountryStateTranslationDefinition::getEntityName(),
            $oldStateData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['countryStateId'] = $state['id'];
        $this->helper->convertValue($translation, 'name', $oldStateData, 'name');
        $this->helper->convertValue($state, 'shortCode', $oldStateData, 'shortcode');
        $this->helper->convertValue($state, 'position', $oldStateData, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($state, 'active', $oldStateData, 'active', $this->helper::TYPE_BOOLEAN);

        $languageData = $this->mappingService->getLanguageUuid($this->profileId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $state['translations'][$languageData['uuid']] = $translation;

        return $state;
    }

    private function getDeliveries(array $data, array $converted): array
    {
        $deliveries = [];

        $delivery = [
            'id' => Uuid::uuid4()->getHex(),
            'orderStateId' => $converted['stateId'],
            'shippingDateEarliest' => $converted['date'],
            'shippingDateLatest' => $converted['date'],
        ];

        if (isset($data['shippingMethod']['id'])) {
            $delivery['shippingMethod'] = $this->getShippingMethod($data['shippingMethod']);
        } else {
            return [];
        }

        if (isset($data['shippingaddress']['id'])) {
            $delivery['shippingOrderAddress'] = $this->getAddress($data['shippingaddress']);
        }

        if (!isset($delivery['shippingOrderAddress'])) {
            $delivery['shippingOrderAddress'] = $this->getAddress($data['billingaddress']);
        }

        if (isset($data['trackingcode']) && $data['trackingcode'] !== '') {
            $delivery['trackingCode'] = $data['trackingcode'];
        }

        $deliveries[] = $delivery;

        return $deliveries;
    }

    private function getShippingMethod(array $originalData): array
    {
        $shippingMethod = [];
        $shippingMethod['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            ShippingMethodDefinition::getEntityName(),
            $originalData['id'],
            $this->context
        );

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            ShippingMethodTranslationDefinition::getEntityName(),
            $originalData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['shippingMethodId'] = $shippingMethod['id'];
        $this->helper->convertValue($translation, 'name', $originalData, 'name');
        $this->helper->convertValue($translation, 'description', $originalData, 'description');
        $this->helper->convertValue($translation, 'comment', $originalData, 'comment');

        $languageData = $this->mappingService->getLanguageUuid($this->profileId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $shippingMethod['translations'][$languageData['uuid']] = $translation;

        $this->helper->convertValue($shippingMethod, 'type', $originalData, 'type', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'bindShippingfree', $originalData, 'bind_shippingfree', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($shippingMethod, 'bindLaststock', $originalData, 'bind_laststock', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($shippingMethod, 'active', $originalData, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($shippingMethod, 'position', $originalData, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'calculation', $originalData, 'calculation', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'surchargeCalculation', $originalData, 'surcharge_calculation', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'taxCalculation', $originalData, 'tax_calculation', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'shippingFree', $originalData, 'shippingfree', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($shippingMethod, 'bindTimeFrom', $originalData, 'bind_time_from', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'bindTimeTo', $originalData, 'bind_time_to', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'bindInstock', $originalData, 'bind_instock', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($shippingMethod, 'bindWeekdayFrom', $originalData, 'bind_weekday_from', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'bindWeekdayTo', $originalData, 'bind_weekday_to', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($shippingMethod, 'bindWeightFrom', $originalData, 'bind_weight_from', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($shippingMethod, 'bindWeightTo', $originalData, 'bind_weight_to', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($shippingMethod, 'bindPriceFrom', $originalData, 'bind_price_from', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($shippingMethod, 'bindPriceTo', $originalData, 'bind_price_to', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($shippingMethod, 'bindSql', $originalData, 'bind_sql');
        $this->helper->convertValue($shippingMethod, 'statusLink', $originalData, 'status_link');
        $this->helper->convertValue($shippingMethod, 'calculationSql', $originalData, 'calculation_sql');

        // TODO sales channel assignment
        return $shippingMethod;
    }

    private function getLineItems(array $originalData): array
    {
        $lineItems = [];

        foreach ($originalData as $originalLineItem) {
            $isProduct = (int) $originalLineItem['modus'] === 0 && (int) $originalLineItem['articleID'] !== 0;
            $lineItem = [
                'id' => Uuid::uuid4()->getHex(),
            ];

            if ($isProduct) {
                if ($originalLineItem['articleordernumber'] !== null) {
                    $lineItem['identifier'] = $this->mappingService->getUuid(
                        $this->profileId,
                        ProductDefinition::getEntityName(),
                        $originalLineItem['articleordernumber'],
                        $this->context
                    );
                }

                if (!isset($lineItem['identifier'])) {
                    $lineItem['identifier'] = 'unmapped-product-' . $originalLineItem['articleordernumber'] . '-' . $originalLineItem['articleID'];
                }

                $lineItem['type'] = ProductCollector::LINE_ITEM_TYPE;
            } else {
                $this->helper->convertValue($lineItem, 'identifier', $originalLineItem, 'articleordernumber');

                $lineItem['type'] = DiscountSurchargeCollector::DATA_KEY;
            }

            $this->helper->convertValue($lineItem, 'quantity', $originalLineItem, 'quantity', $this->helper::TYPE_INTEGER);
            $this->helper->convertValue($lineItem, 'label', $originalLineItem, 'name');
            $this->helper->convertValue($lineItem, 'unitPrice', $originalLineItem, 'price', $this->helper::TYPE_FLOAT);
            $lineItem['totalPrice'] = $lineItem['quantity'] * $lineItem['unitPrice'];

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
