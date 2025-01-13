<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class GlobalDocumentBaseConfigLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<DocumentBaseConfigCollection> $documentBaseConfigRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $documentBaseConfigRepository,
    ) {
    }

    public function get(string $documentTypeId, Context $context): ?string
    {
        if (\array_key_exists($documentTypeId, $this->cache)) {
            return $this->cache[$documentTypeId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('global', true));
        $criteria->addFilter(new EqualsFilter('documentTypeId', $documentTypeId));
        $criteria->setLimit(1);

        $baseConfigId = $this->documentBaseConfigRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$documentTypeId] = $baseConfigId;

        return $baseConfigId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
