<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EntityAlreadyExistsRunLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class LanguageConverter extends ShopwareConverter
{
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

    public function getSourceIdentifier(array $data): string
    {
        return $data['locale'];
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
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::LANGUAGE,
            $data['locale'],
            $context,
            $checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];

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
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $data, $this->mainMapping['id']);
    }
}
