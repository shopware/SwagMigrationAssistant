<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Salutation\SalutationCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class SalutationLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<SalutationCollection> $salutationRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $salutationRepository,
    ) {
    }

    public function get(string $salutationKey, Context $context): ?string
    {
        if (\array_key_exists($salutationKey, $this->cache)) {
            return $this->cache[$salutationKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
        $criteria->setLimit(1);

        $salutationId = $this->salutationRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$salutationKey] = $salutationId;

        return $salutationId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
