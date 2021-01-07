<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider;

use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorRegistry;
use Shopware\Core\Checkout\Document\FileGenerator\FileGeneratorRegistry;
use Shopware\Core\Checkout\Document\GeneratedDocument;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class GenerateDocumentProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var FileGeneratorRegistry
     */
    private $fileGeneratorRegistry;

    /**
     * @var DocumentGeneratorRegistry
     */
    private $documentGeneratorRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $orderRepository,
        FileGeneratorRegistry $fileGeneratorRegistry,
        DocumentGeneratorRegistry $documentGeneratorRegistry
    )
    {
        $this->documentRepository = $documentRepository;
        $this->orderRepository = $orderRepository;
        $this->fileGeneratorRegistry = $fileGeneratorRegistry;
        $this->documentGeneratorRegistry = $documentGeneratorRegistry;
    }

    public function generateDocument(string $identifier, Context $context): ?GeneratedDocument
    {
        $criteria = new Criteria([ $identifier ]);
        $criteria->addAssociation('documentType');
        /** @var DocumentEntity|null $document */
        $document = $this->documentRepository->search($criteria, $context)->first();

        if ($document === null) {
            return null;
        }

        $documentMediaFile = $document->getDocumentMediaFile();
        if ($document->isStatic() || ($documentMediaFile !== null && $documentMediaFile->getFileName() !== null)) {
            return null;
        }

        $config = DocumentConfigurationFactory::createConfiguration($document->getConfig());

        $generatedDocument = new GeneratedDocument();
        $generatedDocument->setPageOrientation($config->getPageOrientation());
        $generatedDocument->setPageSize($config->getPageSize());

        $fileGenerator = $this->fileGeneratorRegistry->getGenerator($document->getFileType());
        $generatedDocument->setContentType($fileGenerator->getContentType());

        $documentType = $document->getDocumentType();
        if ($documentType === null) {
            return null;
        }

        $documentGenerator = $this->documentGeneratorRegistry->getGenerator(
            $documentType->getTechnicalName()
        );

        $order = $this->getOrderById($document->getOrderId(), $document->getOrderVersionId(), $context);

        if ($order === null) {
            return null;
        }

        $generatedDocument->setHtml($documentGenerator->generate($order, $config, $context));
        $generatedDocument->setFilename($documentGenerator->getFileName($config) . '.' . $fileGenerator->getExtension());
        $fileBlob = $fileGenerator->generate($generatedDocument);
        $generatedDocument->setFileBlob($fileBlob);

        return $generatedDocument;
    }

    private function getOrderById(string $orderId, string $versionId, Context $context): ?OrderEntity
    {
        $criteria = (new Criteria([$orderId]))
            ->addAssociation('lineItems')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('currency')
            ->addAssociation('language.locale')
            ->addAssociation('addresses.country')
            ->addAssociation('deliveries.positions')
            ->addAssociation('deliveries.shippingMethod');

        $versionContext = $context->createWithVersionId($versionId);

        return $this->orderRepository->search($criteria, $versionContext)->get($orderId);
    }
}
