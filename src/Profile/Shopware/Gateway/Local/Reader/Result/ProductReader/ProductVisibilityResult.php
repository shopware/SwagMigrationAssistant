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
class ProductVisibilityResult
{
    /**
     * @var array<int|string, array<int, string>>
     */
    private array $productVisibility = [];

    /**
     * @param array<int, string> $shops
     */
    public function add(string $productId, array $shops): void
    {
        $this->productVisibility[$productId] = \array_values(
            \array_unique(
                \array_merge(
                    $shops,
                    $this->productVisibility[$productId] ?? []
                )
            )
        );
    }

    /**
     * @return array<int, string>
     */
    public function getShops(string $productId): array
    {
        $productId = (int) $productId;
        if (!$this->hasShops($productId)) {
            return [];
        }

        return $this->productVisibility[$productId];
    }

    private function hasShops(int $productId): bool
    {
        return isset($this->productVisibility[$productId]) && \is_array($this->productVisibility[$productId]);
    }
}
