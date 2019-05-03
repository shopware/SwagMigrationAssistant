<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Premapping\PaymentMethodReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CustomerConverter extends Shopware55Converter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var string
     */
    private $connectionId;

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

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string[]
     */
    private $requiredDataFieldKeys = [
        'firstname',
        'lastname',
        'email',
        'salutation',
        'customerGroupId',
    ];

    /**
     * @var string[]
     */
    private $requiredAddressDataFieldKeys = [
        'firstname',
        'lastname',
        'zipcode',
        'city',
        'street',
        'salutation',
    ];

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return DefaultEntities::CUSTOMER;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $oldData = $data;
        $this->runId = $migrationContext->getRunUuid();

        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);

        if (!empty($fields)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data fields',
                sprintf('Customer-Entity could not converted cause of empty necessary field(s): %s.', implode(', ', $fields)),
                [
                    'id' => $data['id'],
                    'entity' => 'Customer',
                    'fields' => $fields,
                ],
                \count($fields)
            );

            return new ConvertStruct(null, $oldData);
        }

        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        if (isset($data['accountmode']) && $data['accountmode'] === '1') {
            $this->oldCustomerId = $data['id'];
        } else {
            $this->oldCustomerId = $data['email'];
        }

        $customerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            $this->oldCustomerId,
            $this->context
        );

        $converted['id'] = $customerUuid;
        unset($data['id']);

        $converted['salesChannelId'] = Defaults::SALES_CHANNEL;
        if (isset($data['subshopID'])) {
            $salesChannelId = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $data['subshopID'],
                $this->context
            );

            if ($salesChannelId !== null) {
                $converted['salesChannelId'] = $salesChannelId;
                unset($data['subshopID']);
            }
        }

        $this->convertValue($converted, 'password', $data, 'password');
        $this->convertValue($converted, 'active', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'email', $data, 'email');
        $this->convertValue($converted, 'guest', $data, 'accountmode', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'confirmationKey', $data, 'confirmationkey');
        $this->convertValue($converted, 'newsletter', $data, 'newsletter', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'validation', $data, 'validation');
        $this->convertValue($converted, 'affiliate', $data, 'affiliate', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'referer', $data, 'referer');
        $this->convertValue($converted, 'internalComment', $data, 'internalcomment');
        $this->convertValue($converted, 'failedLogins', $data, 'failedlogins', self::TYPE_INTEGER); // Nötig?
        $this->convertValue($converted, 'title', $data, 'title');
        $this->convertValue($converted, 'firstName', $data, 'firstname');
        $this->convertValue($converted, 'lastName', $data, 'lastname');
        $this->convertValue($converted, 'customerNumber', $data, 'customernumber');
        $this->convertValue($converted, 'birthday', $data, 'birthday', self::TYPE_DATETIME);
        $this->convertValue($converted, 'lockedUntil', $data, 'lockeduntil', self::TYPE_DATETIME);
        $this->convertValue($converted, 'encoder', $data, 'encoder');

        if (!isset($converted['customerNumber']) || $converted['customerNumber'] === '') {
            $converted['customerNumber'] = 'number-' . $this->oldCustomerId;
        }

        $customerGroupUuid = $this->mappingService->getUuid($this->connectionId, DefaultEntities::CUSTOMER_GROUP, $data['customerGroupId'], $context);
        if ($customerGroupUuid === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['groupId'] = $customerGroupUuid;
        unset($data['customerGroupId'], $data['customergroup']);

        if (isset($data['defaultpayment']['id'])) {
            $defaultPaymentMethodUuid = $this->getDefaultPaymentMethod($data['defaultpayment']);

            if ($defaultPaymentMethodUuid === null) {
                return new ConvertStruct(null, $oldData);
            }

            $converted['defaultPaymentMethodId'] = $defaultPaymentMethodUuid;
        }
        unset($data['defaultpayment'], $data['paymentpreset']);

        if (!isset($converted['defaultPaymentMethodId'])) {
            $converted['defaultPaymentMethodId'] = $this->mappingService->getUuid(
                $this->connectionId,
                PaymentMethodReader::getMappingName(),
                'default_payment_method',
                $this->context
            );

            if (!isset($converted['defaultPaymentMethodId'])) {
                $this->loggingService->addWarning(
                    $this->runId,
                    Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                    'Empty necessary data fields',
                    'Customer-Entity could not converted cause of empty necessary field(s): defaultpayment.',
                    [
                        'id' => $this->oldCustomerId,
                        'entity' => 'Customer',
                        'fields' => ['defaultpayment'],
                    ],
                    1
                );

                return new ConvertStruct(null, $oldData);
            }
        }

        $salutationUuid = $this->getSalutation($data['salutation']);
        if ($salutationUuid === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['salutationId'] = $salutationUuid;

        if (isset($data['addresses'])) {
            $this->getAddresses($data, $converted, $customerUuid);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes']);
        }
        unset($data['attributes']);

        unset(
            $data['addresses'],
            $data['salutation'],

            // Legacy data which don't need a mapping or there is no equivalent field
            $data['doubleOptinRegister'],
            $data['doubleOptinEmailSentDate'],
            $data['doubleOptinConfirmDate'],
            $data['sessionID'],
            $data['pricegroupID'],
            $data['login_token'],
            $data['changed'],
            $data['paymentID'],
            $data['firstlogin'],
            $data['lastlogin'],

            // TODO check how to handle these
            $data['language'], // TODO use for sales channel information?
            $data['customerlanguage'] // TODO use for sales channel information?
        );

        if (empty($data)) {
            $data = null;
        }

        if (!isset($converted['defaultBillingAddressId'], $converted['defaultShippingAddressId'])) {
            $this->mappingService->deleteMapping($converted['id'], $this->connectionId, $this->context);

            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::NO_ADDRESS_DATA,
                'No address data',
                'Customer-Entity could not converted cause of empty address data.',
                ['id' => $this->oldCustomerId]
            );

            return new ConvertStruct(null, $oldData);
        }

        return new ConvertStruct($converted, $data);
    }

//    private function getCustomerGroup(array $data): array
//    {
//        $group['id'] = $this->mappingService->createNewUuid(
//            $this->connectionId,
//            DefaultEntities::CUSTOMER_GROUP,
//            $data['id'],
//            $this->context
//        );
//
//        $this->getCustomerGroupTranslation($group, $data);
//        $this->convertValue($group, 'displayGross', $data, 'tax', self::TYPE_BOOLEAN);
//        $this->convertValue($group, 'inputGross', $data, 'taxinput', self::TYPE_BOOLEAN);
//        $this->convertValue($group, 'hasGlobalDiscount', $data, 'mode', self::TYPE_BOOLEAN);
//        $this->convertValue($group, 'percentageGlobalDiscount', $data, 'discount', self::TYPE_FLOAT);
//        $this->convertValue($group, 'minimumOrderAmount', $data, 'minimumorder', self::TYPE_FLOAT);
//        $this->convertValue($group, 'minimumOrderAmountSurcharge', $data, 'minimumordersurcharge', self::TYPE_FLOAT);
//        $this->convertValue($group, 'name', $data, 'description');
//
//        return $group;
//    }
//
//    private function getCustomerGroupTranslation(array &$group, array $data): void
//    {
//        $language = $this->mappingService->getDefaultLanguage($this->context);
//        if ($language->getLocale()->getCode() === $this->mainLocale) {
//            return;
//        }
//
//        $localeTranslation = [];
//        $localeTranslation['customerGroupId'] = $group['id'];
//
//        $this->convertValue($localeTranslation, 'name', $data, 'description');
//
//        $localeTranslation['id'] = $this->mappingService->createNewUuid(
//            $this->connectionId,
//            DefaultEntities::CUSTOMER_GROUP_TRANSLATION,
//            $data['id'] . ':' . $this->mainLocale,
//            $this->context
//        );
//
//        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);
//        $localeTranslation['languageId'] = $languageUuid;
//
//        $group['translations'][$languageUuid] = $localeTranslation;
//    }

    private function getDefaultPaymentMethod(array $originalData): ?string
    {
        $paymentMethodUuid = $this->mappingService->getUuid(
            $this->connectionId,
            PaymentMethodReader::getMappingName(),
            $originalData['id'],
            $this->context
        );

        if ($paymentMethodUuid === null) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::UNKNOWN_PAYMENT_METHOD,
                'Cannot find payment method',
                'Customer-Entity could not converted cause of unknown payment method',
                [
                    'id' => $this->oldCustomerId,
                    'entity' => DefaultEntities::CUSTOMER,
                    'paymentMethod' => $originalData['id'],
                ]
            );
        }

        return $paymentMethodUuid;
    }

    /**
     * @param array[] $originalData
     */
    private function getAddresses(array &$originalData, array &$converted, string $customerUuid): void
    {
        $addresses = [];
        foreach ($originalData['addresses'] as $address) {
            $newAddress = [];

            $fields = $this->checkForEmptyRequiredDataFields($address, $this->requiredAddressDataFieldKeys);
            if (!empty($fields)) {
                $this->loggingService->addInfo(
                    $this->runId,
                    Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                    'Empty necessary data fields for address',
                    sprintf('Address-Entity could not converted cause of empty necessary field(s): %s.', implode(', ', $fields)),
                    [
                        'id' => $this->oldCustomerId,
                        'uuid' => $customerUuid,
                        'entity' => 'Address',
                        'fields' => $fields,
                    ],
                    \count($fields)
                );

                continue;
            }

            $salutationUuid = $this->getSalutation($address['salutation']);
            if ($salutationUuid === null) {
                continue;
            }

            if (isset($data['addresses'])) {
                $this->getAddresses($data, $converted, $customerUuid);
            }

            $newAddress['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::CUSTOMER_ADDRESS,
                $address['id'],
                $this->context
            );
            $newAddress['salutationId'] = $salutationUuid;

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
            if (isset($address['state'])) {
                $newAddress['countryState'] = $this->getCountryState($address['state'], $newAddress['country']);
            }

            $this->convertValue($newAddress, 'firstName', $address, 'firstname');
            $this->convertValue($newAddress, 'lastName', $address, 'lastname');
            $this->convertValue($newAddress, 'zipcode', $address, 'zipcode');
            $this->convertValue($newAddress, 'city', $address, 'city');
            $this->convertValue($newAddress, 'company', $address, 'company');
            $this->convertValue($newAddress, 'street', $address, 'street');
            $this->convertValue($newAddress, 'department', $address, 'department');
            $this->convertValue($newAddress, 'title', $address, 'title');
            $this->convertValue($newAddress, 'vatId', $address, 'ustid');
            $this->convertValue($newAddress, 'phoneNumber', $address, 'phone');
            $this->convertValue($newAddress, 'additionalAddressLine1', $address, 'additional_address_line1');
            $this->convertValue($newAddress, 'additionalAddressLine2', $address, 'additional_address_line2');

            $addresses[] = $newAddress;
        }

        if (empty($addresses)) {
            return;
        }

        $converted['addresses'] = $addresses;

        // No valid default billing and shipping address was converted, so use the first valid one as default
        $this->checkUnsetDefaultShippingAndDefaultBillingAddress($originalData, $converted, $customerUuid, $addresses);

        // No valid default shipping address was converted, but the default billing address is valid
        $this->checkUnsetDefaultShippingAddress($originalData, $converted, $customerUuid);

        // No valid default billing address was converted, but the default shipping address is valid
        $this->checkUnsetDefaultBillingAddress($originalData, $converted, $customerUuid);
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
                $this->connectionId,
                $this->context
            );
        }

        if ($countryUuid !== null) {
            $country['id'] = $countryUuid;
        } else {
            $country['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::COUNTRY,
                $oldCountryData['id'],
                $this->context
            );
        }

        $this->getCountryTranslation($country, $oldCountryData);
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

    private function getCountryTranslation(array &$country, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['countryId'] = $country['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'countryname');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::COUNTRY_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $country['translations'][$languageUuid] = $localeTranslation;
    }

    private function getCountryState(array $oldStateData, array $newCountryData): array
    {
        $state = [];
        $state['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE,
            $oldStateData['id'],
            $this->context
        );
        $state['countryId'] = $newCountryData['id'];

        $this->getCountryStateTranslation($state, $oldStateData);
        $this->convertValue($state, 'name', $oldStateData, 'name');
        $this->convertValue($state, 'shortCode', $oldStateData, 'shortcode');
        $this->convertValue($state, 'position', $oldStateData, 'position', self::TYPE_INTEGER);
        $this->convertValue($state, 'active', $oldStateData, 'active', self::TYPE_BOOLEAN);

        return $state;
    }

    private function getCountryStateTranslation(array &$state, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['categoryId'] = $data['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $translation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $state['translations'][$languageUuid] = $localeTranslation;
    }

    private function checkUnsetDefaultShippingAndDefaultBillingAddress(array &$originalData, array &$converted, string $customerUuid, $addresses): void
    {
        if (!isset($converted['defaultBillingAddressId']) && !isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $addresses[0]['id'];
            $converted['defaultShippingAddressId'] = $addresses[0]['id'];
            unset($originalData['default_billing_address_id'], $originalData['default_shipping_address_id']);

            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::NO_DEFAULT_BILLING_AND_SHIPPING_ADDRESS,
                'No default billing and shipping address',
                'Default billing and shipping address of customer is empty and will set with the first address.',
                [
                    'id' => $this->oldCustomerId,
                    'uuid' => $customerUuid,
                ]
            );
        }
    }

    private function checkUnsetDefaultShippingAddress(array &$originalData, array &$converted, string $customerUuid): void
    {
        if (!isset($converted['defaultShippingAddressId']) && isset($converted['defaultBillingAddressId'])) {
            $converted['defaultShippingAddressId'] = $converted['defaultBillingAddressId'];
            unset($originalData['default_shipping_address_id']);

            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::NO_DEFAULT_SHIPPING_ADDRESS,
                'No default shipping address',
                'Default shipping address of customer is empty and will set with the default billing address.',
                [
                    'id' => $this->oldCustomerId,
                    'uuid' => $customerUuid,
                ]
            );
        }
    }

    private function checkUnsetDefaultBillingAddress(array &$originalData, array &$converted, string $customerUuid): void
    {
        if (!isset($converted['defaultBillingAddressId']) && isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $converted['defaultShippingAddressId'];
            unset($originalData['default_billing_address_id']);

            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::NO_DEFAULT_BILLING_ADDRESS,
                'No default billing address',
                'Default billing address of customer is empty and will set with the default shipping address.',
                [
                    'id' => $this->oldCustomerId,
                    'uuid' => $customerUuid,
                ]
            );
        }
    }

    private function getSalutation(string $salutation): ?string
    {
        $salutationUuid = $this->mappingService->getUuid(
            $this->connectionId,
            SalutationReader::getMappingName(),
            $salutation,
            $this->context
        );

        if ($salutationUuid === null) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::UNKNOWN_CUSTOMER_SALUTATION,
                'Cannot find customer salutation',
                'Customer-Entity could not converted cause of unknown salutation',
                [
                    'id' => $this->oldCustomerId,
                    'entity' => DefaultEntities::CUSTOMER,
                    'salutation' => $salutation,
                ]
            );
        }

        return $salutationUuid;
    }

    private function getAttributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $attribute => $value) {
            if ($attribute === 'id' || $attribute === 'userID') {
                continue;
            }
            $result[DefaultEntities::CUSTOMER . '_' . $attribute] = $value;
        }

        return $result;
    }
}
