<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class ProductProvider extends AbstractProvider
{
    public function __construct(private readonly EntityRepository $productRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PRODUCT;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('properties');
        $criteria->addAssociation('options');
        $criteria->addAssociation('prices');
        $criteria->addAssociation('media.media.tags');
        $criteria->addAssociation('media.media.translations');
        $criteria->addAssociation('visibilities');
        $criteria->addAssociation('configuratorSettings.media');
        $criteria->addSorting(
            new FieldSorting('parentId'), // get 'NULL' parentIds first
            new FieldSorting('id')
        );
        $result = $this->productRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result, [
            // remove write protected fields
            'childCount',
            'autoIncrement',
            'availableStock',
            'available',
            'displayGroup',
            'ratingAverage',
            'categoryTree',
            'listingPrices',
            'sales',
            'tax', // taxId is already provided
            'productId',
            'cheapestPrice',
            'tagIds',
            'categoryIds',
            'streamIds',
            'states',
            'categoriesRo',

            // media
            'mimeType',
            'fileExtension',
            'mediaTypeRaw',
            'metaData',
            'mediaType',
            'mediaId',
            'thumbnails',
            'thumbnailsRo',
            'hasFile',
            'userId', // maybe put back in, if we migrate users

            'canonicalProductId', // ToDo MIG-900: properly migrate this association in a separate DataSet
            'cmsPageId', // ToDo MIG-901: properly migrate this association in a separate DataSet
        ]);

        // cleanup association entities - only ids are needed
        foreach ($cleanResult as &$product) {
            $this->cleanupAssociationToOnlyContainIds($product, 'categories');
            $this->cleanupAssociationToOnlyContainIds($product, 'properties');
            $this->cleanupAssociationToOnlyContainIds($product, 'options');
        }

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->productRepo, $context);
    }
}
