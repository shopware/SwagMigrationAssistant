<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeCollection;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class DocumentTypeLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<DocumentTypeCollection> $documentTypeRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $documentTypeRepository,
    ) {
    }

    public function get(string $technicalName, Context $context): ?string
    {
        if (\array_key_exists($technicalName, $this->cache)) {
            return $this->cache[$technicalName];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));

        $result = $this->documentTypeRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof DocumentTypeEntity) {
            $this->cache[$technicalName] = null;

            return null;
        }

        $this->cache[$technicalName] = $result->getId();

        return $result->getId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
