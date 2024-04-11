<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class DocumentInheritanceProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<DocumentCollection> $documentRepo
     */
    public function __construct(private readonly EntityRepository $documentRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::ORDER_DOCUMENT_INHERITANCE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('referencedDocumentId', null)]));

        $searchResult = $this->documentRepo->search($criteria, $context);

        $result = [];
        /** @var DocumentEntity $document */
        foreach ($searchResult->getElements() as $document) {
            $result[] = [
                'id' => $document->getId(),
                'referencedDocumentId' => $document->getReferencedDocumentId(),
            ];
        }

        return $result;
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('referencedDocumentId', null)]));

        return $this->readTotalFromRepo($this->documentRepo, $context, $criteria);
    }
}
