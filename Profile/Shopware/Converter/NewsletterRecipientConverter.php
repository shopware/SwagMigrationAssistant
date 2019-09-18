<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\UnknownEntityLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Premapping\NewsletterRecipientStatusReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;

abstract class NewsletterRecipientConverter extends ShopwareConverter
{
    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $oldNewsletterRecipientId;

    /**
     * @var string
     */
    protected $runId;

    protected $requiredDataFieldKeys = [
        '_locale',
        'shopId',
    ];

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
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
        $this->runId = $migrationContext->getRunUuid();
        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);

        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::NEWSLETTER_RECIPIENT,
                $data['id'],
                implode(',', $fields)
            ));

            return new ConvertStruct(null, $data);
        }
        $this->connectionId = $migrationContext->getConnection()->getId();
        $oldData = $data;
        $checksum = $this->generateChecksum($data);
        $this->context = $context;
        $this->locale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        $this->oldNewsletterRecipientId = $data['id'];
        $this->mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::NEWSLETTER_RECIPIENT,
            $this->oldNewsletterRecipientId,
            $context,
            $checksum
        );
        $converted['id'] = $this->mapping['entityUuid'];

        $this->convertValue($converted, 'email', $data, 'email');
        $this->convertValue($converted, 'createdAt', $data, 'added', 'datetime');
        $this->convertValue($converted, 'confirmedAt', $data, 'double_optin_confirmed', 'datetime');

        if (isset($data['address'])) {
            $address = $data['address'];
            $this->convertValue($converted, 'firstName', $address, 'firstname');
            $this->convertValue($converted, 'lastName', $address, 'lastname');
            $this->convertValue($converted, 'street', $address, 'street');
            $this->convertValue($converted, 'zipCode', $address, 'zipcode');
            $this->convertValue($converted, 'city', $address, 'city');

            if (isset($address['salutation'])) {
                $salutationUuid = $this->getSalutation($address['salutation']);
                if ($salutationUuid !== null) {
                    $converted['salutationId'] = $salutationUuid;
                }
            }
            unset($data['address'], $address);
        }
        $converted['hash'] = Uuid::randomHex();

        if (isset($converted['confirmedAt'])) {
            $status = 'optIn';
        } else {
            $status = $this->getStatus();
        }

        if ($status === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['status'] = $status;

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $context);
        $converted['languageId'] = $languageUuid;

        $salesChannelUuid = $this->getSalesChannel($data);
        if ($salesChannelUuid === null) {
            return new ConvertStruct(null, $oldData);
        }
        unset($data['shopId']);
        $converted['salesChannelId'] = $salesChannelUuid;

        unset(
            $data['id'],
            $data['groupID'],
            $data['lastmailing'],
            $data['lastread'],
            $data['customer']
        );

        if (empty($data)) {
            $data = null;
        }

        $this->mapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];
        $this->mappingService->updateMapping(
            $this->connectionId,
            DefaultEntities::NEWSLETTER_RECIPIENT,
            $this->mapping['oldIdentifier'],
            $this->mapping,
            $context
        );

        return new ConvertStruct($converted, $data, $this->mapping['id']);
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
                DefaultEntities::NEWSLETTER_RECIPIENT,
                $this->oldNewsletterRecipientId
            ));
        }
        $this->mappingIds[] = $salutationMapping['id'];

        return $salutationMapping['entityUuid'];
    }

    protected function getSalesChannel(array $data): ?string
    {
        if (isset($data['shopId'])) {
            $salesChannelMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $data['shopId'],
                $this->context
            );
        }

        if (!isset($salesChannelMapping)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::NEWSLETTER_RECIPIENT,
                $this->oldNewsletterRecipientId,
                'salesChannel'
            ));

            return null;
        }
        $this->mappingIds[] = $salesChannelMapping['id'];

        return $salesChannelMapping['entityUuid'];
    }

    protected function getStatus(): ?string
    {
        $status = $this->mappingService->getValue(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            'default_newsletter_recipient_status',
            $this->context
        );

        if ($status === null) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::NEWSLETTER_RECIPIENT,
                $this->oldNewsletterRecipientId,
                'status'
            ));
        }

        return $status;
    }
}
