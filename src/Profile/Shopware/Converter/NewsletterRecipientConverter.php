<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\UnknownEntityLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Premapping\NewsletterRecipientStatusReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;

#[Package('services-settings')]
abstract class NewsletterRecipientConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $locale;

    protected string $connectionId;

    protected string $oldNewsletterRecipientId;

    protected string $runId;

    /**
     * @var list<string>
     */
    protected array $requiredDataFieldKeys = [
        '_locale',
        'shopId',
    ];

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
                \implode(',', $fields)
            ));

            return new ConvertStruct(null, $data);
        }
        $oldData = $data;
        $this->generateChecksum($data);
        $this->context = $context;
        $this->locale = $data['_locale'];
        unset($data['_locale']);

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $converted = [];
        $this->oldNewsletterRecipientId = $data['id'];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::NEWSLETTER_RECIPIENT,
            $this->oldNewsletterRecipientId,
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];

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

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
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

            return null;
        }
        $this->mappingIds[] = $salutationMapping['id'];

        return $salutationMapping['entityUuid'];
    }

    /**
     * @param array<string, mixed> $data
     */
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
            NewsletterRecipientStatusReader::SOURCE_ID,
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
