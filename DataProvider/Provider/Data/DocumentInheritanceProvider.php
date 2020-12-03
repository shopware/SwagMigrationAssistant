<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Feature;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class DocumentInheritanceProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepo;

    public function __construct(EntityRepositoryInterface $documentRepo)
    {
        $this->documentRepo = $documentRepo;
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
        $result = $this->documentRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            'config',
            'deepLinkCode',
            'documentTypeId',
            'documentMediaFileId',
            'fileType',
            'orderId',
            'sent',
            'static',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('referencedDocumentId', null)]));

        return $this->readTotalFromRepo($this->documentRepo, $context, $criteria);
    }
}
