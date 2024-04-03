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
class ShopCategoryRelation
{
    private string $shopId;

    private string $categoryId;

    /**
     * @param array<string, string> $relation
     */
    public function __construct(array $relation)
    {
        $this->shopId = $relation['shopId'];
        $this->categoryId = $relation['categoryId'];
    }

    public function isCategory(string $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }
}
