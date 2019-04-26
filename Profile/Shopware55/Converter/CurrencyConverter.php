<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\Aggregate\CurrencyTranslation\CurrencyTranslationDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CurrencyConverter extends Shopware55Converter
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
        return CurrencyDefinition::getEntityName();
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
        $currencyUuid = $this->mappingService->getCurrencyUuid($this->connectionId, $data['currency'], $context);

        if ($currencyUuid !== null) {
            $this->loggingService->addInfo(
                $migrationContext->getRunUuid(),
                LogType::ENTITY_ALREADY_EXISTS,
                'Entity already exists',
                'Curreny-Entity already exists.',
                ['id' => $data['id']]
            );

            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid($this->connectionId, CurrencyDefinition::getEntityName(), $data['currency'], $context);
        $this->getCurrencyTranslation($converted, $data);

        $this->convertValue($converted, 'isDefault', $data, 'standard', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'shortName', $data, 'currency');
        $this->convertValue($converted, 'name', $data, 'name');
        $this->convertValue($converted, 'factor', $data, 'factor', self::TYPE_INTEGER);
        $this->convertValue($converted, 'position', $data, 'position', self::TYPE_INTEGER);
        $this->convertValue($converted, 'symbol', $data, 'templatechar');
        $converted['placedInFront'] = ((int) $data['symbol_position']) > 16;
        $converted['decimalPrecision'] = $context->getCurrencyPrecision();

        return new ConvertStruct($converted, $data);
    }

    private function getCurrencyTranslation(array &$currency, array $data): void
    {
        $languageData = $this->mappingService->getDefaultLanguageUuid($this->context);
        if ($languageData['createData']['localeCode'] === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'shortName', $data, 'currency');
        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CurrencyTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $localeTranslation['language']['id'] = $languageData['uuid'];
            $localeTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $localeTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $localeTranslation['languageId'] = $languageData['uuid'];
        }

        $currency['translations'][$languageData['uuid']] = $localeTranslation;
    }
}
