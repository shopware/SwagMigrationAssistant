<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware6\Gateway\Api;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DisplayWarning;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Shopware6ApiGateway;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TotalReaderInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

class Shopware6ApiGatewayTest extends TestCase
{
    /**
     * @dataProvider provideEnvironments
     *
     * @param array{shopwareVersion: string, defaultLocale: string, defaultCurrency: string, updateAvailable: bool} $source
     * @param array{shopwareVersion: string, defaultLocale: string, defaultCurrency: string} $self
     * @param array{migrationDisabled: bool, displayWarnings: list<DisplayWarning>} $expectation
     */
    public function testReadEnvironmentInformation(array $source, array $self, array $expectation): void
    {
        $shopware6ApiGateway = $this->createShopware6ApiGateway($self['shopwareVersion'], $source['shopwareVersion'], $source['updateAvailable'], $self['defaultCurrency'], $source['defaultCurrency'], $self['defaultLocale'], $source['defaultLocale']);
        $migrationContext = $this->createMigrationContext($self['shopwareVersion']);

        $result = $shopware6ApiGateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());
        $expectedEnvironmentInfo = new EnvironmentInformation(
            'Shopware',
            $source['shopwareVersion'],
            'http://test.local',
            [],
            [],
            new RequestStatusStruct(),
            $expectation['migrationDisabled'],
            $expectation['displayWarnings'],
            $self['defaultCurrency'],
            $source['defaultCurrency'],
            $source['defaultLocale'],
            $self['defaultLocale'],
        );

        static::assertEquals($expectedEnvironmentInfo, $result);
    }

    /**
     * @return list<
     *      array{
     *          source: array{
     *              shopwareVersion: string,
     *              defaultLocale: string,
     *              defaultCurrency: string,
     *              updateAvailable: bool
     *          },
     *          self: array{
     *              shopwareVersion: string,
     *              defaultLocale: string,
     *              defaultCurrency: string
     *          },
     *          expectation: array{
     *              migrationDisabled: bool,
     *              displayWarnings: list<DisplayWarning>
     *          }
     *      }
     * >
     */
    public static function provideEnvironments(): array
    {
        return [
            [ // old major to new major
                'source' => [
                    'shopwareVersion' => '6.4.0.0',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => false,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.6.1',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => true,
                    'displayWarnings' => [
                        new DisplayWarning('swag-migration.index.shopwareMajorVersionText', [
                            'sourceSystem' => 'Shopware 6.4',
                            'targetSystem' => 'Shopware 6.5',
                        ]),
                    ],
                ],
            ],
            [ // new major to old major
                'source' => [
                    'shopwareVersion' => '6.6.0.0',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => false,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.6.1',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => true,
                    'displayWarnings' => [
                        new DisplayWarning('swag-migration.index.shopwareMajorVersionText', [
                            'sourceSystem' => 'Shopware 6.6',
                            'targetSystem' => 'Shopware 6.5',
                        ]),
                    ],
                ],
            ],
            [ // same major but different minors
                'source' => [
                    'shopwareVersion' => '6.5.4.2',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => false,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.6.1',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => false,
                    'displayWarnings' => [],
                ],
            ],
            [ // same major but plugin update available on source system
                'source' => [
                    'shopwareVersion' => '6.5.6.1',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => true,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.6.1',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => false,
                    'displayWarnings' => [
                        new DisplayWarning('swag-migration.index.pluginVersionText', [
                            'sourceSystem' => 'Shopware 6.5',
                            'pluginName' => 'Migration Assistant',
                        ]),
                    ],
                ],
            ],
            [ // old major to new major with plugin update available in source system
                'source' => [
                    'shopwareVersion' => '6.4.0.0',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => true,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.6.1',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => true,
                    'displayWarnings' => [
                        new DisplayWarning('swag-migration.index.shopwareMajorVersionText', [
                            'sourceSystem' => 'Shopware 6.4',
                            'targetSystem' => 'Shopware 6.5',
                        ]),
                        new DisplayWarning('swag-migration.index.pluginVersionText', [
                            'sourceSystem' => 'Shopware 6.4',
                            'pluginName' => 'Migration Assistant',
                        ]),
                    ],
                ],
            ],
            [ // same major into dev major
                'source' => [
                    'shopwareVersion' => '6.5.0.0',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => false,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.9999999.9999999-dev',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => false,
                    'displayWarnings' => [],
                ],
            ],
            [ // old major into dev major
                'source' => [
                    'shopwareVersion' => '6.4.0.0',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => false,
                ],
                'self' => [
                    'shopwareVersion' => '6.5.9999999.9999999-dev',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => true,
                    'displayWarnings' => [
                        new DisplayWarning('swag-migration.index.shopwareMajorVersionText', [
                            'sourceSystem' => 'Shopware 6.4',
                            'targetSystem' => 'Shopware 6.5',
                        ]),
                    ],
                ],
            ],
            [ // dev major into older major
                'source' => [
                    'shopwareVersion' => '6.5.9999999.9999999-dev',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                    'updateAvailable' => false,
                ],
                'self' => [
                    'shopwareVersion' => '6.4.0.0',
                    'defaultLocale' => 'en-GB',
                    'defaultCurrency' => 'EUR',
                ],
                'expectation' => [
                    'migrationDisabled' => true,
                    'displayWarnings' => [
                        new DisplayWarning('swag-migration.index.shopwareMajorVersionText', [
                            'sourceSystem' => 'Shopware 6.5',
                            'targetSystem' => 'Shopware 6.4',
                        ]),
                    ],
                ],
            ],
        ];
    }

    protected function createShopware6ApiGateway(
        string $selfShopwareVersion,
        string $sourceShopwareVersion,
        bool $sourceUpdateAvailable,
        string $selfDefaultCurrency,
        string $sourceDefaultCurrency,
        string $selfDefaultLocale,
        string $sourceDefaultLocale
    ): Shopware6ApiGateway {
        $readerRegistry = new ReaderRegistry([]);
        $environmentReader = $this->createStub(EnvironmentReader::class);
        $environmentReader->method('read')->willReturn([
            'environmentInformation' => [
                'defaultShopLanguage' => $sourceDefaultLocale,
                'defaultCurrency' => $sourceDefaultCurrency,
                'shopwareVersion' => $sourceShopwareVersion,
                'versionText' => $sourceShopwareVersion,
                'revision' => 'c6221a390c0891e4c637b8c75927644ad87bd260',
                'additionalData' => [],
                'updateAvailable' => $sourceUpdateAvailable,
            ],
            'requestStatus' => new RequestStatusStruct(),
        ]);
        $currencyEntity = new CurrencyEntity();
        $currencyEntity->setId(Defaults::CURRENCY);
        $currencyEntity->setIsoCode($selfDefaultCurrency);
        /** @var StaticEntityRepository<CurrencyCollection> $currencyRepo */
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
        $localeEntity->setCode($selfDefaultLocale);
        $languageEntity->setLocale($localeEntity);
        /** @var StaticEntityRepository<LanguageCollection> $languageRepo */
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

        $totalReader = $this->createStub(TotalReaderInterface::class);
        $totalReader->method('readTotals')->willReturn([]);

        $tableReader = $this->createStub(TableReaderInterface::class);
        $tableReader->method('read')->willReturn([]);

        return new Shopware6ApiGateway(
            $readerRegistry,
            $environmentReader,
            $currencyRepo,
            $languageRepo,
            $totalReader,
            $tableReader,
            $selfShopwareVersion,
        );
    }

    protected function createMigrationContext(string $selfShopwareVersion): MigrationContext
    {
        $profile = new Shopware6MajorProfile($selfShopwareVersion);
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setCredentialFields([
            'endpoint' => 'http://test.local',
            'apiUser' => 'dummyUser',
            'apiPassword' => 'dummyPassword',
            'bearer_token' => 'dummyToken',
        ]);

        return new MigrationContext(
            $profile,
            $connection,
            Uuid::randomHex(),
            null,
            0,
            100
        );
    }
}
