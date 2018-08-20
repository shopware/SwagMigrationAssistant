<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Cart\Price\Struct\Price;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\DiscountSurcharge\Cart\DiscountSurchargeCollector;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodTranslation\ShippingMethodTranslationDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Product\Cart\ProductCollector;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Country\CountryDefinition;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

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

    public function __construct(
        Shopware55MappingService $mappingService,
        ConverterHelperService $converterHelperService,
        TaxCalculator $taxCalculator
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->taxCalculator = $taxCalculator;
    }

    public function supports(): string
    {
        return OrderDefinition::getEntityName();
    }

    /**
     * @throws AssociationEntityRequiredMissingException
     */
    public function convert(array $data, Context $context): ConvertStruct
    {
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            OrderDefinition::getEntityName(),
            $data['id'],
            $context
        );
        unset($data['id']);

        $converted['customerId'] = $this->mappingService->getUuid(
            Shopware55Profile::PROFILE_NAME,
            CustomerDefinition::getEntityName(),
            $data['userID'],
            $context
        );
        unset($data['userID'], $data['customer']);

        if ($converted['customerId'] === null) {
            throw new AssociationEntityRequiredMissingException(
                OrderDefinition::getEntityName(),
                CustomerDefinition::getEntityName()
            );
        }

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

        $converted['currencyId'] = Defaults::CURRENCY;
        unset($data['currency'], $data['currencyFactor']);

        $this->getPaymentMethod($data, $converted, $context);
        unset($data['payment'], $data['paymentID']);

        $this->helper->convertValue($converted, 'date', $data, 'ordertime');

        $converted['stateId'] = $this->mappingService->getOrderStateUuid((int) $data['status'], $context);
        unset($data['status'], $data['orderstatus']);

        $converted['billingAddress'] = $this->getAddress($data['billingaddress'], $context);
        unset($data['billingaddress']);

        $converted['deliveries'] = $this->getDeliveries($data, $context, $converted);
        unset($data['trackingcode'], $data['shippingMethod'], $data['dispatchID'], $data['shippingaddress']);

        $converted['lineItems'] = $this->getLineItems($data['details'], $context);
        unset($data['details']);

        $converted['transactions'] = $this->getTransactions($data, $converted, $context);
        unset($data['cleared'], $data['paymentstatus']);

        $converted['salesChannelId'] = Defaults::SALES_CHANNEL;

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

    private function getTransactions(array $data, array $converted, Context $context): array
    {
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
                'orderTransactionStateId' => $this->mappingService->getTransactionStateUuid((int) $data['cleared'], $context),
                'amount' => new Price(
                    $converted['amountTotal'],
                    $converted['amountTotal'],
                    $calculatedTaxes,
                    $taxRules
                ),
            ],
        ];

        return $transactions;
    }

    private function getPaymentMethod(array $originalData, array &$converted, Context $context): void
    {
        $paymentMethodUuid = $this->mappingService->getPaymentUuid($originalData['payment']['name'], $context);

        if ($paymentMethodUuid !== null) {
            $paymentMethod['id'] = $paymentMethodUuid;
        } else {
            $paymentMethod['id'] = $this->mappingService->createNewUuid(
                Shopware55Profile::PROFILE_NAME,
                PaymentMethodDefinition::getEntityName(),
                $originalData['payment']['id'],
                $context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            PaymentMethodTranslationDefinition::getEntityName(),
            $originalData['payment']['id'] . ':' . $this->mainLocale,
            $context
        );

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

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $this->mainLocale, $context);

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

    private function getAddress(array $originalData, Context $context): array
    {
        $address = [];
        $address['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            OrderAddressDefinition::getEntityName(),
            $originalData['id'],
            $context
        );

        $address['countryId'] = $this->mappingService->getUuid(
            Shopware55Profile::PROFILE_NAME,
            CountryDefinition::getEntityName(),
            $originalData['countryID'],
            $context
        );
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

    private function getDeliveries(array $data, Context $context, array $converted): array
    {
        $deliveries = [];

        $delivery = [
            'id' => Uuid::uuid4()->getHex(),
            'shippingAddress' => $this->getAddress($data['shippingaddress'], $context),
            'orderStateId' => $converted['stateId'],
            'shippingMethod' => $this->getShippingMethod($data['shippingMethod'], $context),
            'shippingDateEarliest' => $converted['date'],
            'shippingDateLatest' => $converted['date'],
        ];

        if (isset($data['trackingcode']) && $data['trackingcode'] !== '') {
            $delivery['trackingCode'] = $data['trackingcode'];
        }

        $deliveries[] = $delivery;

        return $deliveries;
    }

    private function getShippingMethod(array $originalData, Context $context): array
    {
        $shippingMethod = [];
        $shippingMethod['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            ShippingMethodDefinition::getEntityName(),
            $originalData['id'],
            $context
        );

        $translation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            ShippingMethodTranslationDefinition::getEntityName(),
            $originalData['id'] . ':' . $this->mainLocale,
            $context
        );

        $translation['shippingMethodId'] = $shippingMethod['id'];
        $this->helper->convertValue($translation, 'name', $originalData, 'name');
        $this->helper->convertValue($translation, 'description', $originalData, 'description');
        $this->helper->convertValue($translation, 'comment', $originalData, 'comment');

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $this->mainLocale, $context);

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

    private function getLineItems(array $originalData, Context $context): array
    {
        $lineItems = [];

        foreach ($originalData as $originalLineItem) {
            $isProduct = (int) $originalLineItem['modus'] === 0 && (int) $originalLineItem['articleID'] !== 0;
            $lineItem = [
                'id' => Uuid::uuid4()->getHex(),
            ];

            if ($isProduct) {
                $lineItem['identifier'] = $this->mappingService->getUuid(
                    Shopware55Profile::PROFILE_NAME,
                    ProductDefinition::getEntityName(),
                    $originalLineItem['articleDetailID'],
                    $context
                );

                if ($lineItem['identifier'] === null) {
                    throw new AssociationEntityRequiredMissingException(
                        OrderLineItemDefinition::getEntityName(),
                        ProductDefinition::getEntityName()
                    );
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
            $lineItem['taxRate'] = (float) $originalLineItem['tax_rate'];

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
