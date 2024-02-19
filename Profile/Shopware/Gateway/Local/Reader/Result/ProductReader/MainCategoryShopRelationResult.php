<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\Result\ProductReader;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('services-settings')]
class MainCategoryShopRelationResult
{
    /**
     * @var array<int, ShopCategoryRelation>
     */
    private array $shopCategoryRelation = [];

    public function add(ShopCategoryRelation $shopCategoryRelation): void
    {
        $this->shopCategoryRelation[] = $shopCategoryRelation;
    }

    /**
     * @return array<int, string>
     */
    public function getShopIds(string $categoryId): array
    {
        $result = \array_filter($this->shopCategoryRelation, function ($shopCategoryRelation) use ($categoryId) {
            return $shopCategoryRelation->getCategoryId() === $categoryId;
        });

        return \array_values(
            \array_map(function (ShopCategoryRelation $shopCategoryRelation) {
                return $shopCategoryRelation->getShopId();
            }, $result)
        );
    }

    public function containsCategory(string $categoryId): bool
    {
        foreach ($this->shopCategoryRelation as $shopCategoryRelation) {
            if ($shopCategoryRelation->isCategory($categoryId)) {
                return true;
            }
        }

        return false;
    }
}
