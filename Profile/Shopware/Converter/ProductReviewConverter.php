<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class ProductReviewConverter extends ShopwareConverter
{
    protected $requiredDataFieldKeys = [
        '_locale',
        'articleID',
        'email',
        'shop_id',
    ];

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var string
     */
    private $mainLocale;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);

        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::PRODUCT_REVIEW,
                $data['id'],
                implode(',', $fields)
            ));

            return new ConvertStruct(null, $data);
        }

        $originalData = $data;
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->mainLocale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_REVIEW,
            $data['id'],
            $context
        );
        unset($data['id']);

        $converted['productId'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_mainProduct',
            $data['articleID'],
            $context
        );

        if ($converted['productId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::PRODUCT,
                    $data['articleID'],
                    DefaultEntities::PRODUCT_REVIEW
            ));

            return new ConvertStruct(null, $originalData);
        }
        unset($data['articleID']);

        $converted['customerId'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::CUSTOMER,
            $data['email'],
            $context
        );

        if ($converted['customerId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::CUSTOMER,
                    $data['email'],
                    DefaultEntities::PRODUCT_REVIEW
                ));

            return new ConvertStruct(null, $originalData);
        }
        unset($data['email']);

        $converted['salesChannelId'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::SALES_CHANNEL,
            $data['shop_id'],
            $context
        );

        if ($converted['salesChannelId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::SALES_CHANNEL,
                    $data['shop_id'],
                    DefaultEntities::PRODUCT_REVIEW
                ));

            return new ConvertStruct(null, $originalData);
        }
        unset($data['shop_id']);

        $converted['languageId'] = $this->mappingService->getLanguageUuid(
            $this->connectionId,
            $this->mainLocale,
            $context
        );

        if ($converted['languageId'] === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $migrationContext->getRunUuid(),
                    DefaultEntities::LANGUAGE,
                    $this->mainLocale,
                    DefaultEntities::PRODUCT_REVIEW
                ));

            return new ConvertStruct(null, $originalData);
        }

        $this->convertValue($converted, 'title', $data, 'headline');
        $this->convertValue($converted, 'content', $data, 'comment');
        $this->convertValue($converted, 'points', $data, 'points', self::TYPE_FLOAT);
        $this->convertValue($converted, 'status', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'comment', $data, 'answer');

        return new ConvertStruct($converted, $data);
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }
}
