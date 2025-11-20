<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\DataProvider\Provider\Data\SystemConfigProvider;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('fundamentals@after-sales')]
class DataProviderControllerTest extends TestCase
{
    use AdminApiTestBehaviour;
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    public function testSystemConfigEntriesGetFiltered(): void
    {
        $salesChannelId = Uuid::randomHex();

        $this->createSalesChannel(['id' => $salesChannelId]);

        $configEntries = [];
        foreach (SystemConfigProvider::$CONFIG_KEY_BLOCK_LIST as $configKey) {
            $configEntries[] = [
                'configurationKey' => $configKey,
                'configurationValue' => 'should be filtered',
                'salesChannelId' => $salesChannelId,
            ];
        }

        $configEntries[] = [
            'configurationKey' => 'core.some.allowedConfigKey',
            'configurationValue' => 'should be visible',
            'salesChannelId' => $salesChannelId,
        ];

        $this->getContainer()->get('system_config.repository')->create($configEntries, Context::createDefaultContext());

        $browser = $this->createClient();

        $browser->request(
            'GET',
            '/api/_action/data-provider/get-data',
            [
                'identifier' => DefaultEntities::SYSTEM_CONFIG,
            ]
        );

        $response = $browser->getResponse()->getContent();

        static::assertNotNull($response);

        $response = json_decode($response, true);

        foreach ($response as $configEntry) {
            static::assertNotContains($configEntry['configurationKey'], SystemConfigProvider::$CONFIG_KEY_BLOCK_LIST);
        }

        static::assertNotEmpty(array_filter($response, static fn ($entry) => $entry['configurationKey'] === 'core.some.allowedConfigKey'));
    }
}
