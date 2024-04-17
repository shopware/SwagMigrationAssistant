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
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class OrderDocumentReader extends AbstractReader
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
        $this->setConnection($migrationContext);
        $documents = $this->mapData($this->fetchDocuments($migrationContext), [], ['document']);

        $locale = $this->getDefaultShopLocale();

        foreach ($documents as &$document) {
            $document['_locale'] = \str_replace('_', '-', $locale);
        }

        return $this->cleanupResultSet($documents);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order_documents')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::ORDER_DOCUMENT, $total);
    }

    private function fetchDocuments(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_order_documents', $migrationContext->getOffset(), $migrationContext->getLimit());

        $query = $this->connection->createQueryBuilder();

        $query->from('s_order_documents', 'document');
        $this->addTableSelection($query, 's_order_documents', 'document');

        $query->leftJoin('document', 's_order_documents_attributes', 'attributes', 'document.id = attributes.documentID');
        $this->addTableSelection($query, 's_order_documents_attributes', 'attributes');

        $query->leftJoin('document', 's_core_documents', 'document_documenttype', 'document.type = document_documenttype.id');
        $this->addTableSelection($query, 's_core_documents', 'document_documenttype');

        $query->where('document.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }
}
