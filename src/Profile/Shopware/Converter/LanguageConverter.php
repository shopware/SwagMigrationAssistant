<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageDefinition;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertEntityAlreadyExistsLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
abstract class LanguageConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $connectionId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected readonly LocaleLookup $localeLookup,
        protected readonly LanguageLookup $languageLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['locale'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        $languageUuid = $this->languageLookup->get($data['locale'], $context);

        if ($languageUuid !== null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(LanguageDefinition::ENTITY_NAME)
                    ->withFieldSourcePath('locale')
                    ->withSourceData($data)
                    ->build(ConvertEntityAlreadyExistsLog::class)
            );

            return new ConvertStruct(null, $data);
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::LANGUAGE,
            $data['locale'],
            $context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityId'];

        $this->convertValue($converted, 'name', $data, 'language');

        $localeUuid = $this->localeLookup->get($data['locale'], $context);
        $converted['localeId'] = $localeUuid;
        $converted['translationCodeId'] = $localeUuid;

        unset(
            $data['id'],
            $data['locale'],
            $data['_locale'],
            $data['translations']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }
}
