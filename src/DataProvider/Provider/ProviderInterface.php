<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface ProviderInterface
{
    public function getIdentifier(): string;

    /**
     * @return array<string|int, mixed>
     */
    public function getProvidedData(int $limit, int $offset, Context $context): array;

    public function getProvidedTotal(Context $context): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function getProvidedTable(Context $context): array;
}
