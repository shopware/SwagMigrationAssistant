<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class TimezoneReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return false;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $result = [
            'timezone' => null,
        ];

        $fields = $migrationContext->getConnection()->getCredentialFields();
        if (!isset($fields['installationRoot']) || !\is_string($fields['installationRoot']) || $fields['installationRoot'] === '') {
            return [];
        }

        $basePath = $fields['installationRoot'];

        $configFile = \rtrim($basePath, '/\\') . '/config.php';
        if (!\is_file($configFile) || !\is_readable($configFile)) {
            return [];
        }

        $swConfig = include $configFile;
        if (!\is_array($swConfig)) {
            return [];
        }

        if (!isset($swConfig['db']['timezone']) || !\is_string($swConfig['db']['timezone']) || $swConfig['db']['timezone'] === '') {
            return [];
        }

        $result['timezone'] = $swConfig['db']['timezone'];

        return [$result];
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }
}
