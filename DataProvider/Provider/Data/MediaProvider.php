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

class MediaProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepo;

    public function __construct(EntityRepositoryInterface $mediaRepo)
    {
        $this->mediaRepo = $mediaRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::MEDIA;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('tags');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->mediaRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            'mimeType',
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
        return $this->readTotalFromRepo($this->mediaRepo, $context);
    }
}
