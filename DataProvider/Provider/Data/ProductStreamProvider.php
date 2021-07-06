<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class ProductStreamProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productStreamRepo;

    public function __construct(EntityRepositoryInterface $productStreamRepo)
    {
        $this->productStreamRepo = $productStreamRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PRODUCT_STREAM;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('categories');
        $criteria->addAssociation('filters');
        $criteria->addAssociation('translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->productStreamRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result, [
            'apiFilter',
            'invalid',
            'productStreamId',
            'parentId',
        ], [
            'parameters'
        ]);

        // cleanup categories - only ids are needed
        foreach ($cleanResult as $key => $stream) {
            if (isset($stream['categories'])) {
                $cleanCategories = [];
                foreach ($stream['categories'] as $category) {
                    $cleanCategories[] = [
                        'id' => $category['id'],
                    ];
                }
                $cleanResult[$key]['categories'] = $cleanCategories;
            }
        }

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->productStreamRepo, $context);
    }
}
