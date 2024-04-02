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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\RuleDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class RuleConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === RuleDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::RULE,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['conditions'])) {
            foreach ($converted['conditions'] as &$condition) {
                if (isset($condition['type']) && $condition['type'] === 'alwaysValid') {
                    unset($condition['value']);
                }

                if (isset($condition['type']) && $condition['type'] === 'customerIsCompany' && !isset($condition['value'])) {
                    $condition['value'] = [
                        'isCompany' => false,
                    ];
                }

                if (isset($condition['type']) && $condition['type'] === 'customerIsNewCustomer' && !isset($condition['value'])) {
                    $condition['value'] = [
                        'isNew' => false,
                    ];
                }

                if (isset($condition['type'], $condition['value']['currencyIds']) && $condition['type'] === 'currency') {
                    $newCurrencies = [];
                    $currencyIds = $condition['value']['currencyIds'];
                    foreach ($currencyIds as $currencyId) {
                        $uuid = $this->getMappingIdFacade(DefaultEntities::CURRENCY, $currencyId);
                        if ($uuid !== null) {
                            $newCurrencies[] = $uuid;
                        }
                    }

                    if (!empty($newCurrencies)) {
                        $condition['value']['currencyIds'] = $newCurrencies;
                    }
                }

                if (isset($condition['type'], $condition['value']['countryIds']) && ($condition['type'] === 'customerBillingCountry' || $condition['type'] === 'customerShippingCountry')) {
                    $newCurrencies = [];
                    $countryIds = $condition['value']['countryIds'];
                    foreach ($countryIds as $countryId) {
                        $uuid = $this->getMappingIdFacade(DefaultEntities::COUNTRY, $countryId);
                        if ($uuid !== null) {
                            $newCurrencies[] = $uuid;
                        }
                    }

                    if (!empty($newCurrencies)) {
                        $condition['value']['countryIds'] = $newCurrencies;
                    }
                }
            }

            $converted['conditions'] = $this->sortConditions($converted['conditions']);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    private function sortConditions(array $conditions): array
    {
        $roots = [];
        $sorted = [];
        $parentMap = [];
        foreach ($conditions as $condition) {
            if (!isset($condition['parentId'])) {
                $roots[] = $condition;
            } else {
                $parentMap[$condition['parentId']][] = $condition;
            }
        }

        foreach ($roots as $root) {
            $sorted[] = $root;

            $this->recursiveAddChildren($root['id'], $parentMap, $sorted);
        }

        return $sorted;
    }

    private function recursiveAddChildren(string $rootId, array &$parentMap, array &$sorted): void
    {
        if (!isset($parentMap[$rootId])) {
            return;
        }

        $children = $parentMap[$rootId];

        foreach ($children as $child) {
            $sorted[] = $child;
            $this->recursiveAddChildren($child['id'], $parentMap, $sorted);
        }
    }
}
