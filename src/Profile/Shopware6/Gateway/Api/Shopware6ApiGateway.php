<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Language\LanguageCollection;
use SwagMigrationAssistant\Migration\DisplayWarning;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TotalReaderInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

#[Package('services-settings')]
class Shopware6ApiGateway implements ShopwareGatewayInterface
{
    final public const GATEWAY_NAME = 'api';

    /**
     * @param EntityRepository<CurrencyCollection> $currencyRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     */
    public function __construct(
        private readonly ReaderRegistryInterface $readerRegistry,
        private readonly EnvironmentReaderInterface $environmentReader,
        private readonly EntityRepository $currencyRepository,
        private readonly EntityRepository $languageRepository,
        private readonly TotalReaderInterface $totalReader,
        private readonly TableReaderInterface $tableReader,
        private readonly string $shopwareVersion,
    ) {
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function getSnippetName(): string
    {
        return 'swag-migration.wizard.pages.connectionCreate.gateways.shopwareApi';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $reader = $this->readerRegistry->getReader($migrationContext);

        return $reader->read($migrationContext);
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        $environmentData = $this->environmentReader->read($migrationContext);
        $environmentDataArray = $environmentData['environmentInformation'];
        $profile = $migrationContext->getProfile();

        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '',
                [],
                [],
                $environmentData['requestStatus']
            );
        }

        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '',
                [],
                [],
                null
            );
        }

        $credentials = $connection->getCredentialFields();

        if ($credentials === null) {
            return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '',
                [],
                [],
                null
            );
        }

        $migrationDisabled = false;
        $displayWarnings = [];
        if (!$this->isMajorVersionMatching($environmentDataArray['shopwareVersion'])) {
            $migrationDisabled = true;
            $displayWarnings[] = new DisplayWarning('swag-migration.index.shopwareMajorVersionText', [
                'sourceSystem' => 'Shopware ' . $this->reduceVersionTextToMajorOnly($environmentDataArray['shopwareVersion']),
                'targetSystem' => 'Shopware ' . $this->reduceVersionTextToMajorOnly($this->shopwareVersion),
            ]);
        }

        if (isset($environmentDataArray['updateAvailable'])) {
            // only show a warning, migration is still allowed
            if ($environmentDataArray['updateAvailable']) {
                $displayWarnings[] = new DisplayWarning('swag-migration.index.pluginVersionText', [
                    'sourceSystem' => 'Shopware ' . $this->reduceVersionTextToMajorOnly($environmentDataArray['shopwareVersion']),
                    'pluginName' => 'Migration Assistant',
                ]);
            }
        }

        $targetSystemCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->getEntities()->get(Defaults::CURRENCY);
        \assert($targetSystemCurrency !== null);
        if (!isset($environmentDataArray['defaultCurrency'])) {
            $environmentDataArray['defaultCurrency'] = $targetSystemCurrency->getIsoCode();
        }

        $criteria = new Criteria([Defaults::LANGUAGE_SYSTEM]);
        $criteria->addAssociation('locale');
        $targetSystemLanguage = $this->languageRepository->search($criteria, $context)->getEntities()->get(Defaults::LANGUAGE_SYSTEM);
        \assert($targetSystemLanguage !== null);

        $targetSystemLocale = $targetSystemLanguage->getLocale();
        \assert($targetSystemLocale !== null);

        $totals = $this->readTotals($migrationContext, $context);

        return new EnvironmentInformation(
            $profile->getSourceSystemName(),
            $environmentDataArray['shopwareVersion'],
            (string) $credentials['endpoint'],
            $totals,
            $environmentDataArray['additionalData'],
            $environmentData['requestStatus'],
            $migrationDisabled,
            $displayWarnings,
            $targetSystemCurrency->getIsoCode(),
            $environmentDataArray['defaultCurrency'],
            $environmentDataArray['defaultShopLanguage'],
            $targetSystemLocale->getCode()
        );
    }

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        return $this->totalReader->readTotals($migrationContext, $context);
    }

    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        return $this->tableReader->read($migrationContext, $tableName, $filter);
    }

    private function isMajorVersionMatching(string $otherVersion): bool
    {
        // like 6.5.9999999.9999999-dev
        $selfVersionParts = \explode('.', $this->shopwareVersion);
        // like 6.4.1
        $otherVersionParts = \explode('.', $otherVersion);
        if (\count($selfVersionParts) < 2 || \count($otherVersionParts) < 2) {
            return false;
        }

        // check that other major version is equal to self major version
        return $otherVersionParts[1] === $selfVersionParts[1];
    }

    private function reduceVersionTextToMajorOnly(string $versionText): string
    {
        $versionParts = \explode('.', $versionText);
        if (\count($versionParts) < 2) {
            return $versionText;
        }

        return \implode('.', [
            $versionParts[0],
            $versionParts[1],
        ]);
    }
}
