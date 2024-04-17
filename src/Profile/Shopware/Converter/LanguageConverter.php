<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EntityAlreadyExistsRunLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class LanguageConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $connectionId;

    public function getSourceIdentifier(array $data): string
    {
        return $data['locale'];
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

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
            $this->checksum
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

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }
}
