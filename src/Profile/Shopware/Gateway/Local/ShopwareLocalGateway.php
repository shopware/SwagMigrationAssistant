<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class ShopwareLocalGateway implements ShopwareGatewayInterface
{
    final public const GATEWAY_NAME = 'local';

    /**
     * @param EntityRepository<CurrencyCollection> $currencyRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     */
    public function __construct(
        private readonly ReaderRegistry $readerRegistry,
        private readonly EnvironmentReaderInterface $localEnvironmentReader,
        private readonly TableReaderInterface $localTableReader,
        private readonly ConnectionFactoryInterface $connectionFactory,
        private readonly EntityRepository $currencyRepository,
        private readonly EntityRepository $languageRepository
    ) {
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function getSnippetName(): string
    {
        return 'swag-migration.wizard.pages.connectionCreate.gateways.shopwareLocal';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $reader = $this->readerRegistry->getReader($migrationContext);

        return $reader->read($migrationContext);
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);
        $profile = $migrationContext->getProfile();

        if ($connection === null) {
            $error = MigrationException::databaseConnectionError();

            return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '-',
                [],
                [],
                new RequestStatusStruct($error->getErrorCode(), $error->getMessage())
            );
        }

        try {
            $connection->connect();
        } catch (\Throwable $e) {
            $error = MigrationException::databaseConnectionError();

            return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '-',
                [],
                [],
                new RequestStatusStruct($error->getErrorCode(), $error->getMessage())
            );
        }
        $connection->close();
        $environmentData = $this->localEnvironmentReader->read($migrationContext);

        $targetSystemCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->get(Defaults::CURRENCY);

        $targetCurrencyIsoCode = '';
        if ($targetSystemCurrency instanceof CurrencyEntity) {
            $targetCurrencyIsoCode = $targetSystemCurrency->getIsoCode();
        }

        if (!isset($environmentData['defaultCurrency']) && $targetSystemCurrency instanceof CurrencyEntity) {
            $environmentData['defaultCurrency'] = $targetCurrencyIsoCode;
        }

        $criteria = new Criteria([Defaults::LANGUAGE_SYSTEM]);
        $criteria->addAssociation('locale');
        $targetSystemLanguage = $this->languageRepository->search($criteria, $context)->get(Defaults::LANGUAGE_SYSTEM);

        $targetLocaleCode = '';
        if ($targetSystemLanguage instanceof LanguageEntity) {
            $targetSystemLocale = $targetSystemLanguage->getLocale();

            if ($targetSystemLocale instanceof LocaleEntity) {
                $targetLocaleCode = $targetSystemLocale->getCode();
            }
        }

        if (!isset($environmentData['defaultShopLanguage'])) {
            $environmentData['defaultShopLanguage'] = $targetLocaleCode;
        }
        $environmentData['defaultShopLanguage'] = \str_replace('_', '-', $environmentData['defaultShopLanguage']);

        $totals = $this->readTotals($migrationContext, $context);

        return new EnvironmentInformation(
            $profile->getSourceSystemName(),
            $profile->getVersion(),
            $environmentData['host'],
            $totals,
            $environmentData['additionalData'],
            new RequestStatusStruct(),
            false,
            [],
            $targetCurrencyIsoCode,
            $environmentData['defaultCurrency'],
            $environmentData['defaultShopLanguage'],
            $targetLocaleCode
        );
    }

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $readers = $this->readerRegistry->getReaderForTotal($migrationContext);

        $totals = [];
        foreach ($readers as $reader) {
            $total = $reader->readTotal($migrationContext);

            if ($total === null) {
                continue;
            }

            $totals[$total->getEntityName()] = $total;
        }

        return $totals;
    }

    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        return $this->localTableReader->read($migrationContext, $tableName, $filter);
    }
}
