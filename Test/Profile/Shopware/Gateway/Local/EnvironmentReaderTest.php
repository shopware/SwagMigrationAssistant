<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

class EnvironmentReaderTest extends TestCase
{
    use LocalCredentialTrait;

    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

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
        static::assertSame('1', $data['additionalData'][0]['id']);
        static::assertSame('3', $data['additionalData'][0]['category_id']);
        static::assertSame('de_DE', $data['additionalData'][0]['locale']['locale']);
        static::assertSame('2', $data['additionalData'][0]['children'][0]['id']);
        static::assertSame('1', $data['additionalData'][0]['children'][0]['main_id']);
        static::assertSame('en_GB', $data['additionalData'][0]['children'][0]['locale']['locale']);
        static::assertSame('39', $data['additionalData'][0]['children'][0]['category_id']);
    }
}
