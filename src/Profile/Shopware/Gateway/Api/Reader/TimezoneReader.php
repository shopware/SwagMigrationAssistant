<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

/**
 * This API helper is used only by the Shopware timezone premapping.
 *
 * This is intentionally not a regular migration reader and must not be resolved
 * through the ReaderRegistry during the migration process. It
 * only exists to fetch the source system timezone from the Migration Connector
 * API in the premapping.
 *
 * The class extends ApiReader only to reuse the existing API client/request
 * handling for connector endpoints instead of duplicating that logic here.
 */
#[Package('after-sales')]
class TimezoneReader extends ApiReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        // TimezoneReader must not be resolved through the ReaderRegistry
        throw MigrationException::readerRegistryUsageNotAllowed(self::class);
    }

    protected function getApiRoute(): string
    {
        return 'SwagMigrationTimezone';
    }
}
