<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\FieldReassignedRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\UnknownEntityLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\InvalidEmailAddressLog;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Package('services-settings')]
abstract class CustomerConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

    protected string $mainLocale;

    protected string $oldCustomerId;

    protected string $runId;

    /**
     * @var list<string>
     */
    protected array $requiredDataFieldKeys = [
        'firstname',
        'lastname',
        'email',
        'salutation',
        'customerGroupId',
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

    protected string $connectionName;

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected ValidatorInterface $validator,
        private readonly EntityRepository $salesChannelRepository
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
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->generateChecksum($data);
        $oldData = $data;
        $this->runId = $migrationContext->getRunUuid();
        $this->migrationContext = $migrationContext;

        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);

        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::CUSTOMER,
                $data['id'],
                \implode(',', $fields)
            ));

            return new ConvertStruct(null, $oldData);
        }

        if (!$this->checkEmailValidity($data['email'])) {
            $this->loggingService->addLogEntry(new InvalidEmailAddressLog(
                $this->runId,
                DefaultEntities::CUSTOMER,
                $data['id'],
                $data['email']
            ));

            return new ConvertStruct(null, $oldData);
        }

        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        $this->connectionName = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
            $this->connectionName = $connection->getName();
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            $data['id'],
            $this->context,
            $this->checksum
        );

        $this->oldCustomerId = $data['id'];

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];

        unset($data['id']);

        if (isset($data['subshopID'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $data['subshopID'],
                $this->context
            );

            if (isset($mapping['entityUuid'])) {
                $converted['salesChannelId'] = $mapping['entityUuid'];
                $this->mappingIds[] = $mapping['id'];
                unset($data['subshopID']);

                if (isset($data['shop']['customer_scope']) && (bool) $data['shop']['customer_scope'] === true) {
                    $converted['boundSalesChannelId'] = $mapping['entityUuid'];
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

        $this->setPassword($data, $converted);

        if (!isset($converted['customerNumber']) || $converted['customerNumber'] === '') {
            $converted['customerNumber'] = 'number-' . $this->oldCustomerId;
        }

        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CUSTOMER_GROUP,
            $data['customerGroupId'],
            $context
        );
        if ($mapping === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['groupId'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        unset($data['customerGroupId'], $data['customergroup']);

        if (isset($data['defaultpayment']['id'])) {
            /**
             * @psalm-suppress PossiblyInvalidArgument
             */
            $defaultPaymentMethodUuid = $this->getDefaultPaymentMethod($data['defaultpayment']);

            if ($defaultPaymentMethodUuid === null) {
                return new ConvertStruct(null, $oldData);
            }

            $converted['defaultPaymentMethodId'] = $defaultPaymentMethodUuid;
        }
        unset($data['defaultpayment'], $data['paymentpreset']);

        if (!isset($converted['defaultPaymentMethodId'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                PaymentMethodReader::getMappingName(),
                PaymentMethodReader::SOURCE_ID,
                $this->context
            );

            if ($mapping === null) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::CUSTOMER,
                    $this->oldCustomerId,
                    'defaultpayment'
                ));

                return new ConvertStruct(null, $oldData);
            }
            $converted['defaultPaymentMethodId'] = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
        }

        $salutationUuid = $this->getSalutation($data['salutation']);
        if ($salutationUuid === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['salutationId'] = $salutationUuid;

        if (isset($data['addresses']) && isset($this->mainMapping['entityUuid'])) {
            $this->applyAddresses($data, $converted, $this->mainMapping['entityUuid']);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::CUSTOMER, $this->connectionName, ['id', 'userID'], $this->context);
        }
        unset($data['attributes']);

        if (isset($data['customerlanguage']['locale'])) {
            $languageUuid = $this->mappingService->getLanguageUuid(
                $this->connectionId,
                $data['customerlanguage']['locale'],
                $context
            );

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

        if (!isset($converted['defaultBillingAddressId'], $converted['defaultShippingAddressId'])) {
            $this->mappingService->deleteMapping($converted['id'], $this->connectionId, $this->context);

            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::CUSTOMER,
                $this->oldCustomerId,
                'address data'
            ));

            return new ConvertStruct(null, $oldData);
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
        $originalEncoder = $data['encoder'];

        if ($originalEncoder === 'md5' || $originalEncoder === 'sha256') {
            $converted['legacyPassword'] = $data['password'];
            $converted['legacyEncoder'] = \ucfirst($originalEncoder);
            unset($data['password'], $data['encoder']);

            return;
        }
        $converted['password'] = $data['password'];
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
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                DefaultEntities::PAYMENT_METHOD,
                $originalData['id'],
                DefaultEntities::CUSTOMER,
                $this->oldCustomerId
            ));

            return null;
        }
        $this->mappingIds[] = $paymentMethodMapping['id'];

        return $paymentMethodMapping['entityUuid'];
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     */
    protected function applyAddresses(array &$originalData, array &$converted, string $customerUuid): void
    {
        $addresses = [];
        $mainVatId = null;
        foreach ($originalData['addresses'] as $address) {
            $newAddress = [];

            $fields = $this->checkForEmptyRequiredDataFields($address, $this->requiredAddressDataFieldKeys);
            if (!empty($fields)) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::CUSTOMER_ADDRESS,
                    $address['id'],
                    \implode(',', $fields)
                ));

                continue;
            }

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
            $newAddress['id'] = $addressMapping['entityUuid'];
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

            if (!isset($this->mainMapping['entityUuid'])) {
                continue;
            }

            $newAddress['customerId'] = $this->mainMapping['entityUuid'];
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
        $this->checkUnsetDefaultShippingAndDefaultBillingAddress($originalData, $converted, $customerUuid, $addresses);

        // No valid default shipping address was converted, but the default billing address is valid
        $this->checkUnsetDefaultShippingAddress($originalData, $converted, $customerUuid);

        // No valid default billing address was converted, but the default shipping address is valid
        $this->checkUnsetDefaultBillingAddress($originalData, $converted, $customerUuid);
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
     * @param array<string, mixed> $newCountryData
     *
     * @return array<string, mixed>
     */
    protected function getCountryState(array $oldStateData, array $newCountryData): array
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
        $state['countryId'] = $newCountryData['id'];

        $this->applyCountryStateTranslation($state, $oldStateData);
        $this->convertValue($state, 'name', $oldStateData, 'name');
        $this->convertValue($state, 'shortCode', $oldStateData, 'shortcode');
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
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['categoryId'] = $data['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::COUNTRY_STATE_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

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
    protected function checkUnsetDefaultShippingAndDefaultBillingAddress(array &$originalData, array &$converted, string $customerUuid, array $addresses): void
    {
        if (!isset($converted['defaultBillingAddressId']) && !isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $addresses[0]['id'];
            $converted['defaultShippingAddressId'] = $addresses[0]['id'];
            unset($originalData['default_billing_address_id'], $originalData['default_shipping_address_id']);

            $this->loggingService->addLogEntry(new FieldReassignedRunLog(
                $this->runId,
                DefaultEntities::CUSTOMER,
                $customerUuid,
                'default billing and shipping address',
                'first address'
            ));
        }
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     */
    protected function checkUnsetDefaultShippingAddress(array &$originalData, array &$converted, string $customerUuid): void
    {
        if (!isset($converted['defaultShippingAddressId']) && isset($converted['defaultBillingAddressId'])) {
            $converted['defaultShippingAddressId'] = $converted['defaultBillingAddressId'];
            unset($originalData['default_shipping_address_id']);

            $this->loggingService->addLogEntry(new FieldReassignedRunLog(
                $this->runId,
                DefaultEntities::CUSTOMER,
                $customerUuid,
                'default shipping address',
                'default billing address'
            ));
        }
    }

    /**
     * @param array<string, mixed> $originalData
     * @param array<string, mixed> $converted
     */
    protected function checkUnsetDefaultBillingAddress(array &$originalData, array &$converted, string $customerUuid): void
    {
        if (!isset($converted['defaultBillingAddressId']) && isset($converted['defaultShippingAddressId'])) {
            $converted['defaultBillingAddressId'] = $converted['defaultShippingAddressId'];
            unset($originalData['default_billing_address_id']);

            $this->loggingService->addLogEntry(new FieldReassignedRunLog(
                $this->runId,
                DefaultEntities::CUSTOMER,
                $customerUuid,
                'default billing address',
                'default shipping address'
            ));
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
            $this->loggingService->addLogEntry(new UnknownEntityLog(
                $this->runId,
                DefaultEntities::SALUTATION,
                $salutation,
                DefaultEntities::CUSTOMER,
                $this->oldCustomerId
            ));

            return null;
        }
        $this->mappingIds[] = $mapping['id'];

        return $mapping['entityUuid'];
    }

    protected function checkEmailValidity(string $email): bool
    {
        $constraint = new Email();
        $errors = $this->validator->validate(
            $email,
            $constraint
        );

        return \count($errors) === 0;
    }
}
