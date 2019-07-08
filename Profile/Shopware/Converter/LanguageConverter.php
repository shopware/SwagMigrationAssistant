<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Logging\LogType;
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

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        $this->connectionId = $migrationContext->getConnection()->getId();
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $context, true);

        if ($languageUuid !== null) {
            $this->loggingService->addInfo(
                $migrationContext->getRunUuid(),
                LogType::ENTITY_ALREADY_EXISTS,
                'Entity already exists',
                'Language-Entity already exists.',
                ['id' => $data['id'], 'locale' => $data['locale'], 'uuid' => $languageUuid]
            );

            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::LANGUAGE, $data['locale'], $context);

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

        return new ConvertStruct($converted, $data);
    }
}
