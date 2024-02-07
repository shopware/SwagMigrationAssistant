<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SeoUrlReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('services-settings')]
class SeoUrlReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private SeoUrlReader $seoUrlReader;

    private MigrationContext $migrationContext;

    private Connection $externalConnection;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->seoUrlReader = new SeoUrlReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new SeoUrlDataSet(),
            50,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());

        $externalConnection = (new ConnectionFactory())->createDatabaseConnection($this->migrationContext);
        if (!$externalConnection instanceof Connection) {
            static::markTestSkipped('External connection could not be established in: ' . self::class . ' ' . __METHOD__ . ' ' . __LINE__);
        }

        $this->externalConnection = $externalConnection;
    }

    public function testRead(): void
    {
        $this->setRouterToLowerValue(false);
        static::assertTrue($this->seoUrlReader->supports($this->migrationContext));

        $data = $this->seoUrlReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('1226', $data[0]['id']);
        static::assertSame('Genusswelten-EN/', $data[0]['path']);
        static::assertSame('0', $data[0]['main']);
        static::assertSame('2', $data[0]['subshopID']);
        static::assertSame('en-GB', $data[0]['_locale']);
        static::assertSame('cat', $data[0]['type']);
        static::assertSame('43', $data[0]['typeId']);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new SeoUrlDataSet(),
            200,
            10
        );
        $data = $this->seoUrlReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('153', $data[0]['id']);
        static::assertSame('Sommerwelten/162/Sommer-Sandale-Pink', $data[0]['path']);
        static::assertSame('1', $data[0]['main']);
        static::assertSame('1', $data[0]['subshopID']);
        static::assertSame('de-DE', $data[0]['_locale']);
        static::assertSame('detail', $data[0]['type']);
        static::assertSame('162', $data[0]['typeId']);
    }

    public function testReadWithLowerUrl(): void
    {
        $this->setRouterToLowerValue(true);
        static::assertTrue($this->seoUrlReader->supports($this->migrationContext));

        $data = $this->seoUrlReader->read($this->migrationContext);

        static::assertCount(10, $data);
        static::assertSame('genusswelten-en/', $data[0]['path']);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new SeoUrlDataSet(),
            200,
            10
        );
        $data = $this->seoUrlReader->read($this->migrationContext);

        static::assertSame('sommerwelten/162/sommer-sandale-pink', $data[0]['path']);
    }

    public function testReadTotal(): void
    {
        static::assertTrue($this->seoUrlReader->supportsTotal($this->migrationContext));

        $totalStruct = $this->seoUrlReader->readTotal($this->migrationContext);
        static::assertInstanceOf(TotalStruct::class, $totalStruct);

        $dataset = $this->migrationContext->getDataSet();
        static::assertInstanceOf(SeoUrlDataSet::class, $dataset);

        static::assertSame($dataset::getEntity(), $totalStruct->getEntityName());
        static::assertSame(495, $totalStruct->getTotal());
    }

    private function setRouterToLowerValue(bool $value): void
    {
        $serializedValue = \serialize($value);

        $elementId = $this->externalConnection->executeQuery(
            'SELECT `id` FROM `s_core_config_elements` WHERE `name` = "routerToLower";'
        )->fetchOne();

        $value = $this->externalConnection->executeQuery(
            'SELECT `value` FROM `s_core_config_values` WHERE `element_id` = :elementId;',
            ['elementId' => (int) $elementId]
        )->fetchOne();

        if (!\is_string($value)) {
            $this->externalConnection->executeQuery(
                'INSERT INTO `s_core_config_values` (`element_id`, `shop_id`, `value`) VALUES (:elementId, :shopId, :value)',
                ['elementId' => $elementId, 'shopId' => 1, 'value' => $serializedValue]
            );

            return;
        }

        $this->externalConnection->executeQuery(
            'UPDATE `s_core_config_values` SET `value` = :value WHERE `element_id` = :elementId AND `shop_id` = 1;',
            ['elementId' => $elementId, 'value' => $serializedValue]
        );
    }
}
