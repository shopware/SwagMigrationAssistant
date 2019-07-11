<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagMigrationAssistant\Migration\DisplayWarning;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableCountReaderInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableReaderInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class ShopwareApiGateway implements ShopwareGatewayInterface
{
    public const GATEWAY_NAME = 'api';

    /**
     * @var ReaderInterface
     */
    private $apiReader;

    /**
     * @var ReaderInterface
     */
    private $environmentReader;

    /**
     * @var TableReaderInterface
     */
    private $tableReader;

    /**
     * @var TableCountReaderInterface
     */
    private $tableCountReader;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        ReaderInterface $apiReader,
        ReaderInterface $environmentReader,
        TableReaderInterface $tableReader,
        TableCountReaderInterface $tableCountReader,
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->apiReader = $apiReader;
        $this->environmentReader = $environmentReader;
        $this->tableReader = $tableReader;
        $this->tableCountReader = $tableCountReader;
        $this->currencyRepository = $currencyRepository;
    }

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        return $this->apiReader->read($migrationContext);
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

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        $updateAvailable = false;
        if (isset($environmentData['environmentInformation']['updateAvailable'])) {
            $updateAvailable = $environmentData['environmentInformation']['updateAvailable'];
        }

        /** @var CurrencyEntity $targetSystemCurrency */
        $targetSystemCurrency = $this->currencyRepository->search(new Criteria([Defaults::CURRENCY]), $context)->get(Defaults::CURRENCY);
        if (!isset($environmentDataArray['defaultCurrency'])) {
            $environmentDataArray['defaultCurrency'] = $targetSystemCurrency->getIsoCode();
        }

        $totals = $this->readTotals($migrationContext, $context);
        $credentials = $migrationContext->getConnection()->getCredentialFields();

        $displayWarnings = [];
        if ($updateAvailable) {
            $displayWarnings[] = new DisplayWarning('swag-migration.index.pluginVersionText', [
                'sourceSystem' => 'Shopware 5',
                'pluginName' => 'Migration Connector',
            ]);
        }

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
        return $this->tableCountReader->readTotals($migrationContext, $context);
    }

    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        return $this->tableReader->read($migrationContext, $tableName, $filter);
    }
}
