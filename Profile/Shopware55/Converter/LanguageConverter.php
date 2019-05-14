<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Logging\LogType;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class LanguageConverter extends Shopware55Converter
{
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
    private $mainLocale;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $connectionId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return DefaultEntities::LANGUAGE;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
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
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $context);

        if ($languageUuid !== null) {
            $this->loggingService->addInfo(
                $migrationContext->getRunUuid(),
                LogType::ENTITY_ALREADY_EXISTS,
                'Entity already exists',
                'Language-Entity already exists.',
                ['id' => $data['id'], 'locale' => $data['locale']]
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
