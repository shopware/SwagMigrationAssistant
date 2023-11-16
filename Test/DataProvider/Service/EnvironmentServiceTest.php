<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\DataProvider\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\AbstractExtensionDataProvider;
use Shopware\Core\Framework\Store\Services\StoreClient;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\DataProvider\Service\EnvironmentService;
use SwagMigrationAssistant\DataProvider\Service\EnvironmentServiceInterface;

#[Package('services-settings')]
class EnvironmentServiceTest extends TestCase
{
    private EnvironmentServiceInterface $environmentService;

    /**
     * @dataProvider provideEnvironments
     */
    public function testGetEnvironmentData(string $shopwareVersion, string $defaultCurrency, string $defaultLocale, bool $updateAvailable): void
    {
        $this->createEnvironmentService($shopwareVersion, $defaultCurrency, $defaultLocale, $updateAvailable);
        $data = $this->environmentService->getEnvironmentData(Context::createDefaultContext());

        static::assertSame($data, [
            'defaultShopLanguage' => $defaultLocale,
            'defaultCurrency' => $defaultCurrency,
            'shopwareVersion' => $shopwareVersion,
            'versionText' => $shopwareVersion,
            'revision' => $shopwareVersion,
            'additionalData' => [],
            'updateAvailable' => $updateAvailable,
        ]);
    }

    public function provideEnvironments(): array
    {
        return [
            ['6.5.6.1', 'EUR', 'de-DE', false],
            ['6.5.6.2', 'USD', 'en-GB', false],
            ['6.5.0.0', 'USD', 'en-GB', true],
        ];
    }

    protected function createEnvironmentService(string $shopwareVersion = '6.5.6.1', string $defaultCurrency = 'EUR', string $defaultLocale = 'de-DE', bool $updateAvailable = false): void
    {
        $currencyEntity = new CurrencyEntity();
        $currencyEntity->setId(Defaults::CURRENCY);
        $currencyEntity->setIsoCode($defaultCurrency);

        $currencyRepo = new StaticEntityRepository(
            [
                new EntitySearchResult(
                    CurrencyDefinition::ENTITY_NAME,
                    1,
                    new EntityCollection([$currencyEntity]),
                    null,
                    new Criteria(),
                    Context::createDefaultContext(),
                ),
            ],
            new CurrencyDefinition(),
        );

        $languageEntity = new LanguageEntity();
        $languageEntity->setId(Defaults::LANGUAGE_SYSTEM);
        $localeEntity = new LocaleEntity();
        $localeEntity->setId(Uuid::randomHex());
        $localeEntity->setCode($defaultLocale);
        $languageEntity->setLocale($localeEntity);

        $languageRepo = new StaticEntityRepository(
            [
                new EntitySearchResult(
                    LanguageDefinition::ENTITY_NAME,
                    1,
                    new EntityCollection([$languageEntity]),
                    null,
                    new Criteria(),
                    Context::createDefaultContext(),
                ),
            ],
            new LanguageDefinition(),
        );

        $storeClientStub = static::createStub(StoreClient::class);
        $storeClientStub->method('getExtensionUpdateList')->willReturn(
            $updateAvailable ? [true] : []
        );
        $extensionDataProviderStub = static::createStub(AbstractExtensionDataProvider::class);

        $this->environmentService = new EnvironmentService(
            $currencyRepo,
            $languageRepo,
            $shopwareVersion,
            $shopwareVersion,
            $storeClientStub,
            $extensionDataProviderStub,
        );
    }
}
