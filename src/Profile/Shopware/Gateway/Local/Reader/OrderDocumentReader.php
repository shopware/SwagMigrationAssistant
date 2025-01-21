<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class OrderDocumentReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::ORDER_DOCUMENT;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $documents = $this->mapData($this->fetchDocuments($migrationContext), [], ['document']);

        $locale = $this->getDefaultShopLocale($migrationContext);

        foreach ($documents as &$document) {
            $document['_locale'] = \str_replace('_', '-', $locale);
        }

        return $this->cleanupResultSet($documents);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order_documents')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::ORDER_DOCUMENT, $total);
    }

    private function fetchDocuments(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers($migrationContext, 's_order_documents', $migrationContext->getOffset(), $migrationContext->getLimit());
        $connection = $this->getConnection($migrationContext);

        $query = $connection->createQueryBuilder();

        $query->from('s_order_documents', 'document');
        $this->addTableSelection($query, 's_order_documents', 'document', $migrationContext);

        $query->leftJoin('document', 's_order_documents_attributes', 'attributes', 'document.id = attributes.documentID');
        $this->addTableSelection($query, 's_order_documents_attributes', 'attributes', $migrationContext);

        $query->leftJoin('document', 's_core_documents', 'document_documenttype', 'document.type = document_documenttype.id');
        $this->addTableSelection($query, 's_core_documents', 'document_documenttype', $migrationContext);

        $query->where('document.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
