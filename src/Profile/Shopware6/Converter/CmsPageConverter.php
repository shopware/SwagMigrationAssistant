<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CmsPageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class CmsPageConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === CmsPageDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        // handle locked default layouts
        if (isset($converted['locked']) && $converted['locked'] === true) {
            $this->mappingService->mapLockedCmsPageUuidByNameAndType(
                \array_column($converted['translations'], 'name'),
                $converted['type'],
                $data['id'],
                $this->connectionId,
                $this->migrationContext,
                $this->context
            );

            return new ConvertStruct(null, $data);
        }

        $this->updateTranslations($converted);
        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CMS_PAGE,
            $data['id'],
            $converted['id']
        );

        if (isset($data['previewMediaId'])) {
            $converted['previewMediaId'] = $this->getMappingIdFacade(DefaultEntities::MEDIA, $data['previewMediaId']);
        }

        if (isset($converted['sections'])) {
            $this->processSubentities($converted['sections']);
        }

        if (isset($data['categories'])) {
            $this->updateAssociationIds(
                $converted['categories'],
                DefaultEntities::CATEGORY,
                'id',
                DefaultEntities::CMS_PAGE
            );
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    protected function processSubentities(array &$sections): void
    {
        foreach ($sections as &$section) {
            if (isset($section['blocks'])) {
                foreach ($section['blocks'] as &$block) {
                    if (isset($block['slots'])) {
                        foreach ($block['slots'] as &$slot) {
                            if (isset($slot['translations'])) {
                                $this->updateAssociationIds(
                                    $slot['translations'],
                                    DefaultEntities::LANGUAGE,
                                    'languageId',
                                    DefaultEntities::CMS_PAGE
                                );
                            }

                            if (isset($slot['backgroundMediaId'])) {
                                $slot['backgroundMediaId'] = $this->getMappingIdFacade(DefaultEntities::MEDIA, $slot['backgroundMediaId']);
                            }
                        }
                        unset($slot);
                    }

                    if (isset($block['backgroundMediaId'])) {
                        $block['backgroundMediaId'] = $this->getMappingIdFacade(DefaultEntities::MEDIA, $block['backgroundMediaId']);
                    }
                }
                unset($block);
            }

            if (isset($section['backgroundMediaId'])) {
                $section['backgroundMediaId'] = $this->getMappingIdFacade(DefaultEntities::MEDIA, $section['backgroundMediaId']);
            }
        }
    }

    private function updateTranslations(array &$converted): void
    {
        $names = \array_column($converted['translations'], 'name');
        $names[] = $converted['name'];
        $duplicatePageUuid = $this->mappingService->getCmsPageUuidByNames($names, $this->context);
        $isDuplicated = $duplicatePageUuid !== null && $converted['id'] !== $duplicatePageUuid;

        if (isset($converted['translations'])) {
            $this->updateAssociationIds(
                $converted['translations'],
                DefaultEntities::LANGUAGE,
                'languageId',
                DefaultEntities::CMS_PAGE
            );

            if ($isDuplicated) {
                foreach ($converted['translations'] as &$translation) {
                    $translation['name'] .= ' (Migration)';
                }
                unset($translation);
            }
        }

        if ($isDuplicated) {
            $converted['name'] .= ' (Migration)';
        }
    }
}
