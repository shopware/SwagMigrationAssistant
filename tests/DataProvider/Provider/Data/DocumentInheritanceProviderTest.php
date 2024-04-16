<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\DataProvider\Provider\Data;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\DataProvider\Provider\Data\DocumentInheritanceProvider;

class DocumentInheritanceProviderTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetProvidedDataShouldUnsetDocumentNumber(): void
    {
        $id = Uuid::randomHex();
        $referencedDocumentId = Uuid::randomHex();
        $documentEntity = $this->getDocumentEntity(['id' => $id, 'referencedDocumentId' => $referencedDocumentId]);

        $context = Context::createDefaultContext();
        $collection = new EntityCollection([$documentEntity]);
        $entityResult = new EntitySearchResult(
            DocumentDefinition::ENTITY_NAME,
            1,
            $collection,
            null,
            new Criteria(['018ee0dc09ae713b8a0d2313c3e8b814']),
            $context
        );

        $documentRepositoryMock = $this->createMock(EntityRepository::class);
        $documentRepositoryMock->method('search')->willReturn($entityResult);

        $provider = new DocumentInheritanceProvider($documentRepositoryMock);
        $result = $provider->getProvidedData(10, 0, $context);

        static::assertCount(1, $result);
        static::assertArrayHasKey(0, $result);

        $relationResult = $result[0];
        static::assertIsArray($relationResult);
        static::assertArrayHasKey('id', $relationResult);
        static::assertArrayHasKey('referencedDocumentId', $relationResult);
        static::assertSame($id, $relationResult['id']);
        static::assertSame($referencedDocumentId, $relationResult['referencedDocumentId']);
    }

    /**
     * @param array<string, mixed> $documentData
     */
    public function getDocumentEntity(array $documentData): DocumentEntity
    {
        $documentEntity = new DocumentEntity();
        $documentEntity->setId($documentData['id'] ?? Uuid::randomHex());
        $documentEntity->setUniqueIdentifier($documentData['uniqueIdentifier'] ?? Uuid::randomHex());
        $documentEntity->setDocumentNumber($documentData['documentNumber'] ?? 'SW10101');
        $documentEntity->setDocumentTypeId($documentData['documentTypeId'] ?? Uuid::randomHex());
        $documentEntity->setDocumentMediaFileId($documentData['documentMediaFileId'] ?? Uuid::randomHex());
        $documentEntity->setFileType('pdf');
        $documentEntity->setOrderId($documentData['orderId'] ?? Uuid::randomHex());
        $documentEntity->setSent($documentData['send'] ?? false);
        $documentEntity->setStatic($documentData['static'] ?? false);
        $documentEntity->setReferencedDocumentId($documentData['referencedDocumentId'] ?? Uuid::randomHex());

        return $documentEntity;
    }
}
