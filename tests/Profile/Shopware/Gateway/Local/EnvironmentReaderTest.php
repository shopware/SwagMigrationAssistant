<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

#[Package('services-settings')]
class EnvironmentReaderTest extends TestCase
{
    use LocalCredentialTrait;

    private EnvironmentReader $environmentReader;

    private MigrationContext $migrationContext;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->environmentReader = new EnvironmentReader(new ConnectionFactory());

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CustomerDataSet(),
            0,
            10
        );

        $this->migrationContext->setGateway(new DummyLocalGateway());
    }

    public function testRead(): void
    {
        $data = $this->environmentReader->read($this->migrationContext);

        static::assertSame('de_DE', $data['defaultShopLanguage']);
        static::assertSame('sw55.local', $data['host']);
        static::assertArrayHasKey(0, $data['additionalData']);
        $additionalData = $data['additionalData'][0];
        static::assertSame('1', $additionalData['id']);
        static::assertSame('3', $additionalData['category_id']);
        static::assertSame('de_DE', $additionalData['locale']['locale']);
        static::assertSame('2', $additionalData['children'][0]['id']);
        static::assertSame('1', $additionalData['children'][0]['main_id']);
        static::assertSame('en_GB', $additionalData['children'][0]['locale']['locale']);
        static::assertSame('39', $additionalData['children'][0]['category_id']);
    }
}
