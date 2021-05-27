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
use Shopware\Core\PlatformRequest;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use Symfony\Component\Routing\RouterInterface;

class DocumentProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepo;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(EntityRepositoryInterface $documentRepo, RouterInterface $router)
    {
        $this->documentRepo = $documentRepo;
        $this->router = $router;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::ORDER_DOCUMENT;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('documentType');
        $criteria->addAssociation('documentMediaFile');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->documentRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result, [
            'documentMediaFileId',
            'documentTypeId',
            'referencedDocumentId', // will be entered in separate, simulated DocumentInheritance entity

            // media
            'mimeType',
            'mediaTypeRaw',
            'metaData',
            'mediaType',
            'mediaId',
            'thumbnails',
            'thumbnailsRo',
            'hasFile',
            'userId', // maybe put back in, if we migrate users
        ], ['config']);

        foreach ($cleanResult as &$document) {
            if (!isset($document['documentMediaFile'])) {
                $document['generateUrl'] = $this->router->generate('api.admin.data-provider.generate-document', [
                    'documentId' => $document['id'],
                ], RouterInterface::ABSOLUTE_URL);

                continue;
            }

            $document['documentMediaFile']['url'] = $this->router->generate('api.action.download.document', [
                'documentId' => $document['id'],
                'deepLinkCode' => $document['deepLinkCode'],
            ], RouterInterface::ABSOLUTE_URL);
        }

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->documentRepo, $context);
    }
}
