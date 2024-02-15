<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class ShippingMethodProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<ShippingMethodCollection> $shippingMethodRepo
     */
    public function __construct(private readonly EntityRepository $shippingMethodRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::SHIPPING_METHOD;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('prices');
        $criteria->addAssociation('tags');
        $criteria->addAssociation('media.tags');
        $criteria->addAssociation('media.translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->shippingMethodRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            'shippingMethodId',
            'deliveryTime',

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
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->shippingMethodRepo, $context);
    }
}
