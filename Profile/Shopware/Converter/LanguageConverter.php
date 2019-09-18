<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EntityAlreadyExistsRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class LanguageConverter extends ShopwareConverter
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var string
     */
    protected $mainLocale;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $connectionId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['locale'];
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $checksum = $this->generateChecksum($data);
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        $this->connectionId = $migrationContext->getConnection()->getId();
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $context, true);

        if ($languageUuid !== null) {
            $this->loggingService->addLogEntry(new EntityAlreadyExistsRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::LANGUAGE,
                $data['id']
            ));

            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $this->mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::LANGUAGE,
            $data['locale'],
            $context,
            $checksum
        );
        $converted['id'] = $this->mapping['entityUuid'];

        $this->convertValue($converted, 'name', $data, 'language');

        $localeUuid = $this->mappingService->getLocaleUuid($this->connectionId, $data['locale'], $context);
        $converted['localeId'] = $localeUuid;
        $converted['translationCodeId'] = $localeUuid;

        unset(
            $data['id'],
            $data['locale'],
            $data['_locale'],
            $data['translations']
        );

        if (empty($data)) {
            $data = null;
        }

        $this->mapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];
        $this->mappingService->updateMapping(
            $this->connectionId,
            DefaultEntities::LANGUAGE,
            $this->mapping['oldIdentifier'],
            $this->mapping,
            $context
        );

        return new ConvertStruct($converted, $data, $this->mapping['id']);
    }
}
