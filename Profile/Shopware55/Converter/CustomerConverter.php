<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroupTranslation\CustomerGroupTranslationDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Country\Aggregate\CountryTranslation\CountryTranslationDefinition;
use Shopware\Core\System\Country\CountryDefinition;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CustomerConverter implements ConverterInterface
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
    private $profile;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $mainLocale;

    /**
     * @var string
     */
    private $oldCustomerId;

    public function __construct(
        Shopware55MappingService $mappingService,
        ConverterHelperService $converterHelperService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
    }

    public function supports(): string
    {
        return CustomerDefinition::getEntityName();
    }

    public function convert(
        array $data,
        Context $context,
        ?string $catalogId = null,
        ?string $salesChannelId = null
    ): ConvertStruct {
        $this->profile = Shopware55Profile::PROFILE_NAME;
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);
        $this->oldCustomerId = $data['id'];

        $converted = [];
        $customerUuid = $this->mappingService->createNewUuid(
            $this->profile,
            CustomerDefinition::getEntityName(),
            $this->oldCustomerId,
            $this->context
        );
        $converted['id'] = $customerUuid;
        unset($data['id']);

        $this->helper->convertValue($converted, 'password', $data, 'password');
        $this->helper->convertValue($converted, 'active', $data, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'email', $data, 'email');
        $this->helper->convertValue($converted, 'accountMode', $data, 'accountmode', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'confirmationKey', $data, 'confirmationkey');
        $this->helper->convertValue($converted, 'newsletter', $data, 'newsletter', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'validation', $data, 'validation');
        $this->helper->convertValue($converted, 'affiliate', $data, 'affiliate', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'referer', $data, 'referer');
        $this->helper->convertValue($converted, 'internalComment', $data, 'internalcomment');
        $this->helper->convertValue($converted, 'failedLogins', $data, 'failedlogins', $this->helper::TYPE_INTEGER); // Nötig?
        $this->helper->convertValue($converted, 'title', $data, 'title');
        $this->helper->convertValue($converted, 'salutation', $data, 'salutation');
        $this->helper->convertValue($converted, 'firstName', $data, 'firstname');
        $this->helper->convertValue($converted, 'lastName', $data, 'lastname');
        $this->helper->convertValue($converted, 'number', $data, 'customernumber');
        $this->helper->convertValue($converted, 'birthday', $data, 'birthday');
        $this->helper->convertValue($converted, 'lockedUntil', $data, 'lockeduntil');
        $this->helper->convertValue($converted, 'encoder', $data, 'encoder');

        if (isset($data['group']['id'])) {
            $converted['group'] = $this->getCustomerGroup($data['group']);
        }
        unset($data['group']);

        if ($data['defaultpayment']['id']) {
            $this->getDefaultPaymentMethod($data, $converted);
        }
        unset($data['defaultpayment'], $data['paymentpreset']);

        if (isset($data['addresses'])) {
            $converted['addresses'] = $this->getAddresses($data, $converted, $customerUuid);
        }
        unset($data['addresses']);

        if ($salesChannelId !== null) {
            $converted['salesChannelId'] = $salesChannelId;
        } else {
            $converted['salesChannelId'] = Defaults::SALES_CHANNEL;
        }

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['doubleOptinRegister'],
            $data['doubleOptinEmailSentDate'],
            $data['doubleOptinConfirmDate'],
            $data['sessionID'],
            $data['pricegroupID'],
            $data['login_token'],
            $data['changed'],
            $data['group']['mode'],
            $data['paymentID'],
            $data['customergroup'],
            $data['firstlogin'],
            $data['lastlogin'],

            // TODO check how to handle these
            $data['shop'], // TODO use for sales channel information?
            $data['subshopID'], // TODO use for sales channel information?
            $data['language'], // TODO use for sales channel information?
            $data['customerlanguage'] // TODO use for sales channel information?
        );

        return new ConvertStruct($converted, $data);
    }

    private function getCustomerGroup(array $originalData): array
    {
        $group['id'] = $this->mappingService->createNewUuid(
            $this->profile,
            CustomerGroupDefinition::getEntityName(),
            $originalData['id'],
            $this->context
        );
        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profile,
            CustomerGroupTranslationDefinition::getEntityName(),
            $originalData['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['customerGroupId'] = $group['id'];
        $this->helper->convertValue($translation, 'name', $originalData, 'description');

        $this->helper->convertValue($group, 'displayGross', $originalData, 'tax', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($group, 'inputGross', $originalData, 'taxinput', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($group, 'hasGlobalDiscount', $originalData, 'mode', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($group, 'percentageGlobalDiscount', $originalData, 'discount', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($group, 'minimumOrderAmount', $originalData, 'minimumorder', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($group, 'minimumOrderAmountSurcharge', $originalData, 'minimumordersurcharge', $this->helper::TYPE_FLOAT);

        $languageData = $this->mappingService->getLanguageUuid($this->profile, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $group['translations'][$languageData['uuid']] = $translation;

        return $group;
    }

    private function getDefaultPaymentMethod(array $originalData, array &$converted): void
    {
        $defaultPaymentMethodUuid = $this->mappingService->getPaymentUuid(
            $originalData['defaultpayment']['name'],
            $this->context
        );

        if ($defaultPaymentMethodUuid !== null) {
            $defaultPaymentMethod['id'] = $defaultPaymentMethodUuid;
        } else {
            $defaultPaymentMethod['id'] = $this->mappingService->createNewUuid(
                $this->profile,
                PaymentMethodDefinition::getEntityName(),
                $originalData['defaultpayment']['id'],
                $this->context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profile,
            PaymentMethodTranslationDefinition::getEntityName(),
            $originalData['defaultpayment']['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $translation['paymentMethodId'] = $defaultPaymentMethod['id'];
        $this->helper->convertValue($translation, 'name', $originalData['defaultpayment'], 'description');
        $this->helper->convertValue($translation, 'additionalDescription', $originalData['defaultpayment'], 'additionaldescription');

        //todo: What about the PluginID?
        $this->helper->convertValue($defaultPaymentMethod, 'technicalName', $originalData['defaultpayment'], 'name');
        $this->helper->convertValue($defaultPaymentMethod, 'template', $originalData['defaultpayment'], 'template');
        $this->helper->convertValue($defaultPaymentMethod, 'class', $originalData['defaultpayment'], 'class');
        $this->helper->convertValue($defaultPaymentMethod, 'table', $originalData['defaultpayment'], 'table');
        $this->helper->convertValue($defaultPaymentMethod, 'hide', $originalData['defaultpayment'], 'hide', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($defaultPaymentMethod, 'percentageSurcharge', $originalData['defaultpayment'], 'debit_percent', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($defaultPaymentMethod, 'absoluteSurcharge', $originalData['defaultpayment'], 'surcharge', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($defaultPaymentMethod, 'surchargeString', $originalData['defaultpayment'], 'surchargestring');
        $this->helper->convertValue($defaultPaymentMethod, 'position', $originalData['defaultpayment'], 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($defaultPaymentMethod, 'active', $originalData['defaultpayment'], 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($defaultPaymentMethod, 'allowEsd', $originalData['defaultpayment'], 'esdactive', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($defaultPaymentMethod, 'usedIframe', $originalData['defaultpayment'], 'embediframe');
        $this->helper->convertValue($defaultPaymentMethod, 'hideProspect', $originalData['defaultpayment'], 'hideprospect', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($defaultPaymentMethod, 'action', $originalData['defaultpayment'], 'action');
        $this->helper->convertValue($defaultPaymentMethod, 'source', $originalData['defaultpayment'], 'source', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($defaultPaymentMethod, 'mobileInactive', $originalData['defaultpayment'], 'mobile_inactive', $this->helper::TYPE_BOOLEAN);

        $languageData = $this->mappingService->getLanguageUuid($this->profile, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $defaultPaymentMethod['translations'][$languageData['uuid']] = $translation;

        $converted['defaultPaymentMethod'] = $defaultPaymentMethod;
    }

    private function getAddresses(array &$originalData, array &$converted, string $customerUuid): array
    {
        $addresses = [];
        foreach ($originalData['addresses'] as $address) {
            $newAddress = [];

            $newAddress['id'] = $this->mappingService->createNewUuid(
                $this->profile,
                CustomerAddressDefinition::getEntityName(),
                $address['id'],
                $this->context
            );

            if (isset($originalData['default_billing_address_id']) && $address['id'] === $originalData['default_billing_address_id']) {
                $converted['defaultBillingAddressId'] = $newAddress['id'];
                unset($originalData['default_billing_address_id']);
            }

            if (isset($originalData['default_shipping_address_id']) && $address['id'] === $originalData['default_shipping_address_id']) {
                $converted['defaultShippingAddressId'] = $newAddress['id'];
                unset($originalData['default_shipping_address_id']);
            }

            $newAddress['customerId'] = $customerUuid;
            $newAddress['country'] = $this->getCountry($address['country']);
            $this->helper->convertValue($newAddress, 'salutation', $address, 'salutation');
            $this->helper->convertValue($newAddress, 'firstName', $address, 'firstname');
            $this->helper->convertValue($newAddress, 'lastName', $address, 'lastname');
            $this->helper->convertValue($newAddress, 'zipcode', $address, 'zipcode');
            $this->helper->convertValue($newAddress, 'city', $address, 'city');
            $this->helper->convertValue($newAddress, 'company', $address, 'company');
            $this->helper->convertValue($newAddress, 'street', $address, 'street');
            $this->helper->convertValue($newAddress, 'department', $address, 'department');
            $this->helper->convertValue($newAddress, 'title', $address, 'title');
            $this->helper->convertValue($newAddress, 'vatId', $address, 'ustid');
            $this->helper->convertValue($newAddress, 'phoneNumber', $address, 'phone');
            $this->helper->convertValue($newAddress, 'additionalAddressLine1', $address, 'additional_address_line1');
            $this->helper->convertValue($newAddress, 'additionalAddressLine2', $address, 'additional_address_line2');

            $addresses[] = $newAddress;
        }

        return $addresses;
    }

    private function getCountry(array $oldCountryData): array
    {
        $country = [];
        $countryUuid = null;
        if (isset($oldCountryData['countryiso'], $oldCountryData['iso3'])) {
            $countryUuid = $this->mappingService->getCountryUuid(
                $oldCountryData['id'],
                $oldCountryData['countryiso'],
                $oldCountryData['iso3'],
                $this->profile,
                $this->context
            );
        }

        if ($countryUuid !== null) {
            $country['id'] = $countryUuid;
        } else {
            $country['id'] = $this->mappingService->createNewUuid(
                $this->profile,
                CountryDefinition::getEntityName(),
                $oldCountryData['id'],
                $this->context
            );
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->profile,
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

        $languageData = $this->mappingService->getLanguageUuid($this->profile, $this->mainLocale, $this->context);

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
}