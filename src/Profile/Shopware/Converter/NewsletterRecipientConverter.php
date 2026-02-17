<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertEntityUnknownLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertObjectTypeUnsupportedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertSourceDataIncompleteLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Premapping\NewsletterRecipientStatusReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;

#[Package('fundamentals@after-sales')]
abstract class NewsletterRecipientConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $locale;

    protected string $connectionId;

    protected string $oldNewsletterRecipientId;

    protected string $runId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected readonly LanguageLookup $languageLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext,
    ): ConvertStruct {
        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();
        $this->runId = $migrationContext->getRunUuid();
        $oldData = $data;

        if (!isset($data['_locale']) || $data['_locale'] === '') {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(NewsletterRecipientDefinition::ENTITY_NAME)
                    ->withFieldSourcePath('_locale')
                    ->withSourceData($data)
                    ->build(ConvertSourceDataIncompleteLog::class)
            );

            return new ConvertStruct(null, $oldData);
        }

        $this->generateChecksum($data);
        $this->context = $context;
        $this->locale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        $this->oldNewsletterRecipientId = $data['id'];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::NEWSLETTER_RECIPIENT,
            $this->oldNewsletterRecipientId,
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityId'];

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
                $salutationUuid = $this->getSalutation($address['salutation'], $migrationContext);
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
            $status = $this->getStatus($migrationContext);
        }

        if ($status === null) {
            return new ConvertStruct(null, $oldData);
        }

        $converted['languageId'] = $this->languageLookup->get($this->locale, $context);
        $converted['salesChannelId'] = $this->getSalesChannel($data);

        unset(
            $data['shopId'],
            $data['id'],
            $data['groupID'],
            $data['lastmailing'],
            $data['lastread'],
            $data['customer']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    protected function getSalutation(string $salutation, MigrationContextInterface $migrationContext): ?string
    {
        $salutationMapping = $this->mappingService->getMapping(
            $this->connectionId,
            SalutationReader::getMappingName(),
            $salutation,
            $this->context
        );

        if ($salutationMapping === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(NewsletterRecipientDefinition::ENTITY_NAME)
                    ->withFieldName('salutationId')
                    ->withSourceData(['salutation' => $salutation])
                    ->build(ConvertEntityUnknownLog::class)
            );

            return null;
        }
        $this->mappingIds[] = $salutationMapping['id'];

        return $salutationMapping['entityId'];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function getSalesChannel(array $data): ?string
    {
        if (!isset($data['shopId']) || $data['shopId'] === '') {
            return null;
        }

        $salesChannelMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $data['shopId'],
            $this->context
        );

        if ($salesChannelMapping === null) {
            return null;
        }

        $this->mappingIds[] = $salesChannelMapping['id'];

        return $salesChannelMapping['entityId'];
    }

    protected function getStatus(MigrationContextInterface $migrationContext): ?string
    {
        $status = $this->mappingService->getValue(
            $this->connectionId,
            NewsletterRecipientStatusReader::getMappingName(),
            NewsletterRecipientStatusReader::SOURCE_ID,
            $this->context
        );

        if ($status === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(NewsletterRecipientDefinition::ENTITY_NAME)
                    ->withFieldName('status')
                    ->withFieldSourcePath('status')
                    ->withSourceData(['status' => NewsletterRecipientStatusReader::SOURCE_ID])
                    ->build(ConvertObjectTypeUnsupportedLog::class)
            );

            return null;
        }

        return $status;
    }
}
