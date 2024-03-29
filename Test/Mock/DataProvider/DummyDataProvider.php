<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\DataProvider;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Tax\TaxCollection;
use SwagMigrationAssistant\DataProvider\Provider\Data\AbstractProvider;

#[Package('services-settings')]
class DummyDataProvider extends AbstractProvider
{
    public function getIdentifier(): string
    {
        return 'DUMMY_IDENTIFIER';
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        return [];
    }

    public function getProvidedTotal(Context $context): int
    {
        return 0;
    }

    /**
     * @param TaxCollection|CategoryCollection $collection
     * @param list<string> $writeProtectedFieldKeys
     *
     * @return array<array<string, mixed>>
     */
    public function cleanupProvidedData(EntityCollection $collection, array $writeProtectedFieldKeys = []): array
    {
        return $this->cleanupSearchResult($collection, $writeProtectedFieldKeys);
    }
}
