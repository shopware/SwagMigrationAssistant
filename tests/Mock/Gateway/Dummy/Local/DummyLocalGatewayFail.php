<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;

#[Package('fundamentals@after-sales')]
class DummyLocalGatewayFail implements GatewayInterface
{
    final public const GATEWAY_NAME = 'local';

    final public const ERROR_CODE = 'SWAG_MIGRATION__LOCAL_DATABASE_CONNECTION_ERROR';

    final public const ERROR_MESSAGE = 'Connection to database failed';

    public function getName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function supports(ProfileInterface $profile): bool
    {
        return $profile instanceof ProfileInterface;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        return [];
    }

    public function readEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        $profile = $migrationContext->getProfile();

        return new EnvironmentInformation(
                $profile->getSourceSystemName(),
                $profile->getVersion(),
                '',
                [],
                [],
                new RequestStatusStruct(
                    self::ERROR_CODE,
                    self::ERROR_MESSAGE,
                    false,
                    MigrationException::connectionValidationFailed(
                        self::ERROR_CODE,
                        self::ERROR_MESSAGE,
                    ),
                ),
            );
    }

    public function readTotals(MigrationContextInterface $migrationContext): array
    {
        return [];
    }

    public function getSnippetName(): string
    {
        return 'myFailingSnippetName';
    }
}
