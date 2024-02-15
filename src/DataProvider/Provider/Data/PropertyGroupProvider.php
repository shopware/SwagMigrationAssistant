<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class PropertyGroupProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<PropertyGroupCollection> $propertyGroupRepo
     */
    public function __construct(private readonly EntityRepository $propertyGroupRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PROPERTY_GROUP;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('options.translations');
        $criteria->addAssociation('options.media.translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->propertyGroupRepo->search($criteria, $context);

        return $this->cleanupSearchResult(
            $result,
            [
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
            ]
        );
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->propertyGroupRepo, $context);
    }
}
