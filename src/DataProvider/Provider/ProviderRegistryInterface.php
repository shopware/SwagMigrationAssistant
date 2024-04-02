<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface ProviderRegistryInterface
{
    public function getDataProvider(string $identifier): ProviderInterface;

    /**
     * @param string[] $identifierArray
     *
     * @return ProviderInterface[] Every provider which identifier matches one in the $identifierArray
     */
    public function getDataProviderArray(array $identifierArray): array;

    /**
     * @return ProviderInterface[]
     */
    public function getAllDataProviders(): array;
}
