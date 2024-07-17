<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SeoUrlReader;
use SwagMigrationAssistant\Test\LocalConnectionTestCase;

#[Package('services-settings')]
class SeoUrlReaderTest extends LocalConnectionTestCase
{
    public function testRead(): void
    {
        $seoUrlReader = $this->getSeoUrlReader();

        $this->setRouterToLowerValue(false);
        static::assertTrue($seoUrlReader->supports($this->getMigrationContext()));

        $this->setLimitAndOffset(20, 40);
        $data = $seoUrlReader->read($this->getMigrationContext());

        $seoUrl = $this->findSeoUrl('1226', $data);

        static::assertCount(20, $data);
        static::assertSame('1226', $seoUrl['id']);
        static::assertSame('Genusswelten-EN/', $seoUrl['path']);
        static::assertSame('0', $seoUrl['main']);
        static::assertSame('2', $seoUrl['subshopID']);
        static::assertSame('en-GB', $seoUrl['_locale']);
        static::assertSame('cat', $seoUrl['type']);
        static::assertSame('43', $seoUrl['typeId']);

        $this->setLimitAndOffset(10, 200);
        $data = $seoUrlReader->read($this->getMigrationContext());

        $seoUrl = $this->findSeoUrl('153', $data);

        static::assertCount(10, $data);
        static::assertSame('153', $seoUrl['id']);
        static::assertSame('Sommerwelten/162/Sommer-Sandale-Pink', $seoUrl['path']);
        static::assertSame('1', $seoUrl['main']);
        static::assertSame('1', $seoUrl['subshopID']);
        static::assertSame('de-DE', $seoUrl['_locale']);
        static::assertSame('detail', $seoUrl['type']);
        static::assertSame('162', $seoUrl['typeId']);
    }

    public function testReadWithLowerUrl(): void
    {
        $seoUrlReader = $this->getSeoUrlReader();

        $this->setRouterToLowerValue(true);
        static::assertTrue($seoUrlReader->supports($this->getMigrationContext()));

        $this->setLimitAndOffset(20, 40);
        $data = $seoUrlReader->read($this->getMigrationContext());

        $seoUrl = $this->findSeoUrl('1226', $data);

        static::assertCount(20, $data);
        static::assertSame('genusswelten-en/', $seoUrl['path']);

        $this->setLimitAndOffset(10, 200);
        $data = $seoUrlReader->read($this->getMigrationContext());

        static::assertSame('sommerwelten/162/sommer-sandale-pink', $data[0]['path']);
    }

    public function testReadTotal(): void
    {
        $seoUrlReader = $this->getSeoUrlReader();

        static::assertTrue($seoUrlReader->supportsTotal($this->getMigrationContext()));

        $totalStruct = $seoUrlReader->readTotal($this->getMigrationContext());
        static::assertInstanceOf(TotalStruct::class, $totalStruct);

        $dataset = $this->getMigrationContext()->getDataSet();
        static::assertInstanceOf(SeoUrlDataSet::class, $dataset);

        static::assertSame($dataset::getEntity(), $totalStruct->getEntityName());
        static::assertSame(495, $totalStruct->getTotal());
    }

    protected function getDataSet(): DataSet
    {
        return new SeoUrlDataSet();
    }

    private function setRouterToLowerValue(bool $value): void
    {
        $serializedValue = \serialize($value);

        $connection = $this->getExternalConnection();
        $elementId = $connection->executeQuery(
            'SELECT `id` FROM `s_core_config_elements` WHERE `name` = "routerToLower";'
        )->fetchOne();

        $value = $connection->executeQuery(
            'SELECT `value` FROM `s_core_config_values` WHERE `element_id` = :elementId;',
            ['elementId' => (int) $elementId]
        )->fetchOne();

        if (!\is_string($value)) {
            $connection->executeQuery(
                'INSERT INTO `s_core_config_values` (`element_id`, `shop_id`, `value`) VALUES (:elementId, :shopId, :value)',
                ['elementId' => $elementId, 'shopId' => 1, 'value' => $serializedValue]
            );

            return;
        }

        $connection->executeQuery(
            'UPDATE `s_core_config_values` SET `value` = :value WHERE `element_id` = :elementId AND `shop_id` = 1;',
            ['elementId' => $elementId, 'value' => $serializedValue]
        );
    }

    private function getSeoUrlReader(): SeoUrlReader
    {
        return new SeoUrlReader($this->getConnectionFactory());
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<string, mixed>
     */
    private function findSeoUrl(string $id, array $data): array
    {
        foreach ($data as $seoUrl) {
            if ($seoUrl['id'] === $id) {
                return $seoUrl;
            }
        }

        return [];
    }
}
