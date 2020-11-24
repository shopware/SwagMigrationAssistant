<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TotalReaderInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

class Shopware6ApiGateway implements ShopwareGatewayInterface
{
    public const GATEWAY_NAME = 'api';

    /**
     * @var ReaderRegistryInterface
     */
    private $readerRegistry;

    /**
     * @var EnvironmentReaderInterface
     */
    private $environmentReader;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    /**
     * @var TotalReaderInterface
     */
    private $totalReader;

    /**
     * @var TableReaderInterface
     */
    private $tableReader;

    public function __construct(
        ReaderRegistryInterface $readerRegistry,
        EnvironmentReaderInterface $environmentReader,
        EntityRepositoryInterface $currencyRepository,
        TotalReaderInterface $totalReader,
        TableReaderInterface $tableReader
    ) {
        $this->readerRegistry = $readerRegistry;
        $this->environmentReader = $environmentReader;
        $this->currencyRepository = $currencyRepository;
        $this->totalReader = $totalReader;
        $this->tableReader = $tableReader;
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

        $updateAvailable = false;
        if (isset($environmentData['environmentInformation']['updateAvailable'])) {
            $updateAvailable = $environmentData['environmentInformation']['updateAvailable'];
        }

        $displayWarnings = [];
        /*
        if ($updateAvailable) {
            ToDo@MJ implement proper version validation to make sure the other shopware instance is compatible with this one.
            ToDo@MJ show the user an appropriate error message
        }
        */

        /** @var CurrencyEntity $targetSystemCurrency */
        $targetSystemCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->get(Defaults::CURRENCY);
        if (!isset($environmentDataArray['defaultCurrency'])) {
            $environmentDataArray['defaultCurrency'] = $targetSystemCurrency->getIsoCode();
        }

        $totals = $this->readTotals($migrationContext, $context);

        return new EnvironmentInformation(
            $profile->getSourceSystemName(),
            $environmentDataArray['shopwareVersion'],
            $credentials['endpoint'],
            $totals,
            $environmentDataArray['additionalData'],
            $environmentData['requestStatus'],
            $updateAvailable,
            $displayWarnings,
            $targetSystemCurrency->getIsoCode(),
            $environmentDataArray['defaultCurrency']
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
}
