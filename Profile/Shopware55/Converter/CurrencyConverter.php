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
        return DefaultEntities::CURRENCY;
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
                'Currency-Entity already exists.',
                ['id' => $data['id']]
            );

            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::CURRENCY, $data['currency'], $context);
        $this->getCurrencyTranslation($converted, $data);

        $this->convertValue($converted, 'isDefault', $data, 'standard', self::TYPE_BOOLEAN);
        $converted['shortName'] = $data['currency'];
        $converted['isoCode'] = $data['currency'];
        unset($data['currency']);
        $this->convertValue($converted, 'name', $data, 'name');
        $this->convertValue($converted, 'factor', $data, 'factor', self::TYPE_INTEGER);
        $this->convertValue($converted, 'position', $data, 'position', self::TYPE_INTEGER);
        $this->convertValue($converted, 'symbol', $data, 'templatechar');
        $converted['placedInFront'] = ((int) $data['symbol_position']) > 16;
        $converted['decimalPrecision'] = $context->getCurrencyPrecision();

        unset(
            $data['id'],
            $data['symbol_position'],
            $data['_locale']
        );

        return new ConvertStruct($converted, $data);
    }

    private function getCurrencyTranslation(array &$currency, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->mainLocale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'shortName', $data, 'currency');
        $this->convertValue($localeTranslation, 'name', $data, 'name');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CURRENCY_TRANSLATION,
            $data['id'] . ':' . $this->mainLocale,
            $this->context
        );
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->mainLocale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $currency['translations'][$languageUuid] = $localeTranslation;
    }
}
