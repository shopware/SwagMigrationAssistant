<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\NewsletterReceiver\NewsletterReceiverDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalesChannelReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class NewsletterReceiverConverter extends AbstractConverter
{
    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    private $oldNewsletterReceiverId;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $connectionId;

    private $runId;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return NewsletterReceiverDefinition::getEntityName();
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
        $this->context = $context;
        $this->locale = $data['_locale'];
        $oldData = $data;
        unset($data['_locale']);
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->runId = $migrationContext->getRunUuid();

        $converted = [];
        $this->oldNewsletterReceiverId = $data['id'];
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            NewsletterReceiverDefinition::getEntityName(),
            $this->oldNewsletterReceiverId,
            $context
        );

        $this->helper->convertValue($converted, 'firstName', $data, 'firstname');
        $this->helper->convertValue($converted, 'email', $data, 'email');
        $this->helper->convertValue($converted, 'lastName', $data, 'lastname');
        $this->helper->convertValue($converted, 'street', $data, 'street');
        $this->helper->convertValue($converted, 'zipCode', $data, 'zipcode');
        $this->helper->convertValue($converted, 'city', $data, 'city');
        $this->helper->convertValue($converted, 'createdAt', $data, 'added', 'datetime');

        if (!isset($data['double_optin_confirmed'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data fields',
                'NewsletterReceiver-Entity could not converted cause of empty necessary field(s): double_optin_confirmed.',
                [
                    'id' => $this->oldNewsletterReceiverId,
                    'entity' => NewsletterReceiverDefinition::getEntityName(),
                    'fields' => ['double_optin_confirmed'],
                ],
                1
            );

            return new ConvertStruct(null, $oldData);
        }

        // TODO check why in definition is not required, but on mysql is required
        // TODO map to real shopware 6 status values - double_optin_confirmed type is DATETIME
        $converted['status'] = '1';

        $salutationUuid = $this->getSalutation($data['salutation']);
        if ($salutationUuid === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['salutationId'] = $salutationUuid;

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $context);
        $converted['languageId'] = $languageUuid['uuid'];

        $salesChannelUuid = $this->getSalesChannel();
        if ($salesChannelUuid === null) {
            return new ConvertStruct(null, $oldData);
        }
        $converted['salesChannelId'] = $salesChannelUuid;

        /*
         * Unset fields which are not imported
         */
        unset(
            $data['id'],
            $data['salutation'],
            $data['updated_at'],
            $data['title'],
            $data['groupID'],
            $data['double_optin_confirmed'],
            $data['deleted']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
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
                'NewsletterReceiver-Entity could not converted cause of unknown salutation',
                [
                    'id' => $this->oldNewsletterReceiverId,
                    'entity' => NewsletterReceiverDefinition::getEntityName(),
                    'salutation' => $salutation,
                ]
            );
        }

        return $salutationUuid;
    }

    private function getSalesChannel(): ?string
    {
        $salesChannellUuid = $this->mappingService->getUuid(
            $this->connectionId,
            SalesChannelReader::getMappingName(),
            'default_salesChannel',
            $this->context
        );

        if ($salesChannellUuid === null) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data fields',
                'NewsletterReceiver-Entity could not converted cause of empty necessary field(s): salesChannel.',
                [
                    'id' => $this->oldNewsletterReceiverId,
                    'entity' => NewsletterReceiverDefinition::getEntityName(),
                    'fields' => ['salesChannel'],
                ],
                1
            );
        }

        return $salesChannellUuid;
    }
}
