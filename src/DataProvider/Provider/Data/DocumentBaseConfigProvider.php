<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class DocumentBaseConfigProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<DocumentBaseConfigCollection> $documentBaseConfigRepo
     */
    public function __construct(private readonly EntityRepository $documentBaseConfigRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::ORDER_DOCUMENT_BASE_CONFIG;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('documentType');
        $criteria->addAssociation('logo');
        $criteria->addAssociation('salesChannels.documentType');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->documentBaseConfigRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            'documentTypeId',
            'documentBaseConfigId',
            'logoId',

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
        ], ['config']);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->documentBaseConfigRepo, $context);
    }
}
