<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;

class DummyLocalGateway implements GatewayInterface
{
    public const GATEWAY_NAME = 'local';

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getConnection()->getProfileName() === Shopware55Profile::PROFILE_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $dataSet = $migrationContext->getDataSet();

        switch ($dataSet::getEntity()) {
            case ProductDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/product_data.php';
            case TranslationDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/translation_data.php';
            case CategoryDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/category_data.php';
            case MediaDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/media_data.php';
            case CustomerDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/customer_data.php';
            case OrderDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/order_data.php';
            //Invalid data
            case InvalidCustomerDataSet::getEntity():
                return require __DIR__ . '/../../../../_fixtures/invalid/customer_data.php';
            default:
                return [];
        }
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext): EnvironmentInformation
    {
        $environmentData = require __DIR__ . '/../../../../_fixtures/environment_data.php';

        $environmentDataArray = $environmentData['environmentInformation'];

        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '',
                [],
                [],
                $environmentData['requestStatus']
            );
        }

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        $totals = [
            DefaultEntities::CATEGORY => new TotalStruct(DefaultEntities::CATEGORY, $environmentDataArray['categories']),
            DefaultEntities::PRODUCT => new TotalStruct(DefaultEntities::PRODUCT, $environmentDataArray['products']),
            DefaultEntities::CUSTOMER => new TotalStruct(DefaultEntities::CUSTOMER, $environmentDataArray['customers']),
            DefaultEntities::ORDER => new TotalStruct(DefaultEntities::ORDER, $environmentDataArray['orders']),
            DefaultEntities::MEDIA => new TotalStruct(DefaultEntities::MEDIA, $environmentDataArray['assets']),
            DefaultEntities::TRANSLATION => new TotalStruct(DefaultEntities::TRANSLATION, $environmentDataArray['translations']),
        ];

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            $environmentDataArray['shopwareVersion'],
            $environmentDataArray['additionalData'][0]['host'],
            $totals,
            $environmentDataArray['additionalData'],
            $environmentData['requestStatus']
        );
    }

    public function readTotals(MigrationContextInterface $migrationContext): array
    {
        return [];
    }
}
