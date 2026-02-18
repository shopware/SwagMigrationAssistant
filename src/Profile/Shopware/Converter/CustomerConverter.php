<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertEntityUnknownLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertFieldReassignedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertSourceDataIncompleteLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Package('fundamentals@after-sales')]
abstract class CustomerConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

    protected string $mainLocale;

    protected string $oldCustomerId;

    protected string $runId;

    protected string $connectionName;

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected ValidatorInterface $validator,
        protected readonly EntityRepository $salesChannelRepository,
        protected readonly CountryLookup $countryLookup,
        protected readonly LanguageLookup $languageLookup,
        protected readonly CountryStateLookup $countryStateLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext,
    ): ConvertStruct {
        $this->generateChecksum($data);
        $this->runId = $migrationContext->getRunUuid();
        $this->migrationContext = $migrationContext;

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();
        $this->connectionName = $connection->getName();

        if (!isset($data['_locale']) || $data['_locale'] === '') {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(CustomerDefinition::ENTITY_NAME)
                    ->withFieldSourcePath('_locale')
                    ->withSourceData($data)
                    ->build(ConvertSourceDataIncompleteLog::class)
            );

            return new ConvertStruct(null, $data);
        }
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            $data['id'],
            $this->context,
            $this->checksum
        );

        $this->oldCustomerId = $data['id'];

        $converted = [];
        $converted['id'] = $this->mainMapping['entityId'];

        unset($data['id']);

        if (isset($data['subshopID'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $data['subshopID'],
                $this->context
            );

            if (isset($mapping['entityId'])) {
                $converted['salesChannelId'] = $mapping['entityId'];
                $this->mappingIds[] = $mapping['id'];
                unset($data['subshopID']);

                if (isset($data['shop']['customer_scope']) && (bool) $data['shop']['customer_scope'] === true) {
                    $converted['boundSalesChannelId'] = $mapping['entityId'];
                }
            }
        }

        unset($data['shop']);

        if (empty($converted['salesChannelId'])) {
            $criteria = new Criteria();
            $criteria->setLimit(1);
            $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
            $converted['salesChannelId'] = $this->salesChannelRepository->searchIds($criteria, $context)->firstId();
        }

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

        $this->setAccountType($data, $converted);
        $this->setPassword($data, $converted);

        if (!isset($converted['customerNumber']) || $converted['customerNumber'] === '') {
            $converted['customerNumber'] = 'number-' . $this->oldCustomerId;
        }

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $data['customerGroupId'] ?? '',
            $context
        );

        if (\is_array($mapping) && \array_key_exists('entityId', $mapping)) {
            $converted['groupId'] = $mapping['entityId'];
            $this->mappingIds[] = $mapping['id'];
            unset($data['customerGroupId'], $data['customergroup']);
        }

        if (isset($data['defaultpayment']['id'])) {
            $defaultPaymentMethodUuid = $this->getDefaultPaymentMethod($data['defaultpayment']);

            if ($defaultPaymentMethodUuid !== null) {
                $converted['defaultPaymentMethodId'] = $defaultPaymentMethodUuid;
            }
        }

        unset($data['defaultpayment'], $data['paymentpreset']);

        if (!isset($converted['defaultPaymentMethodId'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                PaymentMethodReader::getMappingName(),
                PaymentMethodReader::SOURCE_ID,
                $this->context
            );

            if (\is_array($mapping) && \array_key_exists('entityId', $mapping)) {
                $converted['defaultPaymentMethodId'] = $mapping['entityId'];
                $this->mappingIds[] = $mapping['id'];
            }
        }

        if (isset($data['salutation']) && $data['salutation'] !== '') {
            $salutationUuid = $this->getSalutation($data['salutation']);

            if ($salutationUuid !== null) {
                $converted['salutationId'] = $salutationUuid;
            }
        }

        if (isset($data['addresses']) && \is_array($data['addresses']) && isset($this->mainMapping['entityId'])) {
            $this->applyAddresses($data, $converted);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::CUSTOMER, $this->connectionName, ['id', 'userID'], $this->context);
        }
        unset($data['attributes']);

        if (isset($data['customerlanguage']['locale'])) {
            $languageUuid = $this->languageLookup->get($data['customerlanguage']['locale'], $context);
            if ($languageUuid !== null) {
                $converted['languageId'] = $languageUuid;
            }
        }

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
            $data['language'],
            $data['customerlanguage']
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
    protected function setPassword(array &$data, array &$converted): void
    {
        $originalEncoder = $data['encoder'] ?? null;

        if ($originalEncoder === 'md5' || $originalEncoder === 'sha256') {
            if (isset($data['password'])) {
                $converted['legacyPassword'] = $data['password'];
            }

            $converted['legacyEncoder'] = \ucfirst($originalEncoder);
            unset($data['password'], $data['encoder']);

            return;
        }

        if (isset($data['password'])) {
            $converted['password'] = $data['password'];
        }

        unset($data['password'], $data['encoder']);
    }

    /**
     * @param array<string, mixed> $originalData
     */
    protected function getDefaultPaymentMethod(array $originalData): ?string
    {
        $paymentMethodMapping = $this->mappingService->getMapping(
            $this->connectionId,
            PaymentMethodReader::getMappingName(),
            $originalData['id'],
            $this->context
        );

        if ($paymentMethodMapping === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerDefinition::ENTITY_NAME)
                    ->withFieldName('defaultPaymentMethodId')
                    ->withFieldSourcePath('default_payment_method')
                    ->withSourceData($originalData)
                    ->build(ConvertEntityUnknownLog::class)
            );

            return null;
        }
        $this->mappingIds[] = $paymentMethodMapping['id'];

        return $paymentMethodMapping['entityId'];
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     */
    protected function applyAddresses(array &$originalData, array &$converted): void
    {
        $addresses = [];
        $mainVatId = null;

        foreach ($originalData['addresses'] as $address) {
            if (!\is_array($address) || !isset($address['id']) || $address['id'] === '') {
                continue;
            }

            $newAddress = [];
            $salutationUuid = $this->getSalutation($address['salutation']);

            if ($salutationUuid === null) {
                continue;
            }

            $addressMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::CUSTOMER_ADDRESS,
                $address['id'],
                $this->context
            );
            $newAddress['id'] = $addressMapping['entityId'];
            $this->mappingIds[] = $addressMapping['id'];
            $newAddress['salutationId'] = $salutationUuid;

            if (isset($originalData['default_billing_address_id']) && $address['id'] === $originalData['default_billing_address_id']) {
                $converted['defaultBillingAddressId'] = $newAddress['id'];
                unset($originalData['default_billing_address_id']);
                if (empty($mainVatId) && isset($address['ustid']) && $address['ustid'] !== '') {
                    $mainVatId = $address['ustid'];
                }
            }

            if (isset($originalData['default_shipping_address_id']) && $address['id'] === $originalData['default_shipping_address_id']) {
                $converted['defaultShippingAddressId'] = $newAddress['id'];
                unset($originalData['default_shipping_address_id']);
                if (isset($address['ustid']) && $address['ustid'] !== '') {
                    $mainVatId = $address['ustid'];
                }
            }

            if (!isset($this->mainMapping['entityId'])) {
                continue;
            }

            $newAddress['customerId'] = $this->mainMapping['entityId'];
            $newAddress['country'] = $this->getCountry($address['country']);

            $countryState = $this->getCountryState($address, $newAddress['country']['id']);
            if (!empty($countryState)) {
                $newAddress['countryState'] = $countryState;
            }

            $this->convertValue($newAddress, 'firstName', $address, 'firstname');
            $this->convertValue($newAddress, 'lastName', $address, 'lastname');
            $this->convertValue($newAddress, 'zipcode', $address, 'zipcode');
            $this->convertValue($newAddress, 'city', $address, 'city');
            $this->convertValue($newAddress, 'company', $address, 'company');
            $this->convertValue($newAddress, 'street', $address, 'street');
            $this->convertValue($newAddress, 'department', $address, 'department');
            $this->convertValue($newAddress, 'title', $address, 'title');
            $this->convertValue($newAddress, 'phoneNumber', $address, 'phone');
            $this->convertValue($newAddress, 'additionalAddressLine1', $address, 'additional_address_line1');
            $this->convertValue($newAddress, 'additionalAddressLine2', $address, 'additional_address_line2');

            if (isset($address['ustid']) && $address['ustid'] !== '') {
                $converted['vatIds'][] = $address['ustid'];
            }
            $addresses[] = $newAddress;
        }

        if (empty($addresses)) {
            return;
        }

        if (isset($mainVatId)) {
            \array_unshift($converted['vatIds'], $mainVatId);
            $converted['vatIds'] = \array_unique($converted['vatIds']);
        }

        $converted['addresses'] = $addresses;

        // No valid default billing and shipping address was converted, so use the first valid one as default
        $this->checkUnsetDefaultShippingAndDefaultBillingAddress($originalData, $converted, $addresses);

        // No valid default shipping address was converted, but the default billing address is valid
        $this->checkUnsetDefaultShippingAddress($originalData, $converted);

        // No valid default billing address was converted, but the default shipping address is valid
        $this->checkUnsetDefaultBillingAddress($originalData, $converted);
    }

    /**
     * @param array<string, mixed> $oldCountryData
     *
     * @return array<string, mixed>
     */
    protected function getCountry(array $oldCountryData): array
    {
        $country = [];
        $countryUuid = null;
        if (isset($oldCountryData['countryiso'], $oldCountryData['iso3'])) {
            $countryUuid = $this->countryLookup->getByIso3($oldCountryData['iso3'], $this->context);
        }

        if ($countryUuid !== null) {
            $country['id'] = $countryUuid;
        } else {
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::COUNTRY,
                $oldCountryData['id'],
                $this->context
            );
            $country['id'] = $mapping['entityId'];
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
        $language = $this->languageLookup->getLanguageEntity($this->context);
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
        $localeTranslation['id'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->languageLookup->get($this->mainLocale, $this->context);
        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $country['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<string, mixed> $oldAddressData
     *
     * @return array<string, mixed>
     */
    protected function getCountryState(array $oldAddressData, string $newCountryId): array
    {
        if (!isset($oldAddressData['state_id'])) {
            return [];
        }

        $state = ['countryId' => $newCountryId];

        if (!isset($oldAddressData['state_id'], $oldAddressData['country']['countryiso'], $oldAddressData['state']['shortcode'])) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerAddressDefinition::ENTITY_NAME)
                    ->withFieldName('stateId')
                    ->withFieldSourcePath('state_id')
                    ->withSourceData($oldAddressData)
                    ->build(ConvertEntityUnknownLog::class)
            );

            return [];
        }

        $countryStateUuid = $this->countryStateLookup->get(
            $oldAddressData['country']['countryiso'],
            $oldAddressData['state']['shortcode'],
            $this->context
        );

        if ($countryStateUuid !== null) {
            $state['id'] = $countryStateUuid;
            $state['shortCode'] = $oldAddressData['country']['countryiso'] . '-' . $oldAddressData['state']['shortcode'];

            return $state;
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE,
            $oldAddressData['state_id'],
            $this->context
        );

        if (!isset(
            $oldAddressData['state']['name'],
            $oldAddressData['state']['shortcode'],
            $oldAddressData['state']['position'],
            $oldAddressData['state']['active']
        )) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerAddressDefinition::ENTITY_NAME)
                    ->withFieldName('stateId')
                    ->withFieldSourcePath('state.name')
                    ->withSourceData($oldAddressData['state'])
                    ->build(ConvertEntityUnknownLog::class)
            );

            return [];
        }

        $state['id'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];

        $oldStateData = $oldAddressData['state'];

        $this->applyCountryStateTranslation($state, $oldStateData);
        $this->convertValue($state, 'name', $oldStateData, 'name');

        $state['shortCode'] = $oldAddressData['country']['countryiso'] . '-' . $oldStateData['shortcode'];
        unset($oldStateData['shortcode']);

        $this->convertValue($state, 'position', $oldStateData, 'position', self::TYPE_INTEGER);
        $this->convertValue($state, 'active', $oldStateData, 'active', self::TYPE_BOOLEAN);

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $data
     */
    protected function applyCountryStateTranslation(array &$state, array $data): void
    {
        $language = $this->languageLookup->getLanguageEntity($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['countryStateId'] = $state['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->languageLookup->get($this->mainLocale, $this->context);
        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $state['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     * @param array<int, array<string, mixed>> $addresses
     */
    protected function checkUnsetDefaultShippingAndDefaultBillingAddress(array &$originalData, array &$converted, array $addresses): void
    {
        if (!isset($converted['defaultBillingAddressId']) && !isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $addresses[0]['id'];
            $converted['defaultShippingAddressId'] = $addresses[0]['id'];
            unset($originalData['default_billing_address_id'], $originalData['default_shipping_address_id']);

            $this->loggingService->addLogForEach(
                [
                    'defaultBillingAddressId' => 'default_billing_address_id',
                    'defaultShippingAddressId' => 'default_shipping_address_id',
                ],
                fn (string $key, string $value) => MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerAddressDefinition::ENTITY_NAME)
                    ->withFieldName($key)
                    ->withFieldSourcePath($value)
                    ->withSourceData($originalData)
                    ->build(ConvertFieldReassignedLog::class)
            );
        }
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     */
    protected function checkUnsetDefaultShippingAddress(array &$originalData, array &$converted): void
    {
        if (!isset($converted['defaultShippingAddressId']) && isset($converted['defaultBillingAddressId'])) {
            $converted['defaultShippingAddressId'] = $converted['defaultBillingAddressId'];
            unset($originalData['default_shipping_address_id']);

            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerAddressDefinition::ENTITY_NAME)
                    ->withFieldName('defaultShippingAddressId')
                    ->withFieldSourcePath('default_shipping_address_id')
                    ->withSourceData($originalData)
                    ->build(ConvertFieldReassignedLog::class)
            );
        }
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     */
    protected function checkUnsetDefaultBillingAddress(array &$originalData, array &$converted): void
    {
        if (!isset($converted['defaultBillingAddressId']) && isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $converted['defaultShippingAddressId'];
            unset($originalData['default_billing_address_id']);

            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerAddressDefinition::ENTITY_NAME)
                    ->withFieldName('defaultBillingAddressId')
                    ->withFieldSourcePath('default_billing_address_id')
                    ->withSourceData($originalData)
                    ->build(ConvertFieldReassignedLog::class)
            );
        }
    }

    protected function getSalutation(string $salutation): ?string
    {
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            SalutationReader::getMappingName(),
            $salutation,
            $this->context
        );

        if ($mapping === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(CustomerDefinition::ENTITY_NAME)
                    ->withFieldName('salutationId')
                    ->withFieldSourcePath('salutation')
                    ->withSourceData(['salutation' => $salutation])
                    ->build(ConvertEntityUnknownLog::class)
            );

            return null;
        }
        $this->mappingIds[] = $mapping['id'];

        return $mapping['entityId'];
    }

    /**
     * If the customer's default billing address or default shipping address contains a company,
     * the account type is business, else private.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    private function setAccountType(array $data, array &$converted): void
    {
        $converted['accountType'] = CustomerEntity::ACCOUNT_TYPE_PRIVATE;

        $defaultBillingAddress = isset($data['default_billing_address_id']) ? $this->getAddressWithId($data, $data['default_billing_address_id']) : null;

        if ($defaultBillingAddress !== null
            && isset($defaultBillingAddress['company'])
            && $defaultBillingAddress['company'] !== ''
        ) {
            $converted['accountType'] = CustomerEntity::ACCOUNT_TYPE_BUSINESS;
            $converted['company'] = $defaultBillingAddress['company'];

            return;
        }

        $defaultShippingAddress = isset($data['default_shipping_address_id']) ? $this->getAddressWithId($data, $data['default_shipping_address_id']) : null;

        if ($defaultShippingAddress !== null
            && isset($defaultShippingAddress['company'])
            && $defaultShippingAddress['company'] !== ''
        ) {
            $converted['accountType'] = CustomerEntity::ACCOUNT_TYPE_BUSINESS;
            $converted['company'] = $defaultShippingAddress['company'];
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    private function getAddressWithId(array $data, string $id): ?array
    {
        if (!isset($data['addresses']) || !\is_array($data['addresses'])) {
            return null;
        }

        foreach ($data['addresses'] as $address) {
            if (!isset($address['id'])) {
                continue;
            }

            if ($address['id'] === $id) {
                return $address;
            }
        }

        return null;
    }
}
