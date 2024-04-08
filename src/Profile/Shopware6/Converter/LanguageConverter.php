<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class LanguageConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === LanguageDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $this->checkDataForDefaultLanguage($data);
        $languageId = $this->mappingService->getLanguageUuid(
            $this->connectionId,
            $data['locale']['code'],
            $this->context,
            true
        );

        if ($languageId !== null) {
            $converted['id'] = $languageId;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::LANGUAGE,
            $data['id'],
            $converted['id']
        );

        $localeUuid = $this->mappingService->getLocaleUuid(
            $this->connectionId,
            $data['locale']['code'],
            $this->context
        );
        $converted['localeId'] = $localeUuid;
        $converted['translationCodeId'] = $localeUuid;
        unset($converted['locale']);

        if (isset($data['parentId'])) {
            $converted['parentId'] = $this->getMappingIdFacade(
                DefaultEntities::LANGUAGE,
                $data['parentId']
            );
        }

        return new ConvertStruct($converted, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function checkDataForDefaultLanguage(array $data): array
    {
        $defaultLanguage = $this->mappingService->getDefaultLanguage($this->context);
        if (!$defaultLanguage instanceof LanguageEntity) {
            return $data;
        }

        $defaultLocale = $defaultLanguage->getLocale();
        if (!$defaultLocale instanceof LocaleEntity) {
            return $data;
        }

        if ($data['id'] === $defaultLanguage->getId() && $data['locale']['code'] !== $defaultLocale->getCode()) {
            // The ID the of given language will newly generated, because the current default language should not be overwritten
            $data['id'] = Uuid::randomHex();
        }

        return $data;
    }
}
