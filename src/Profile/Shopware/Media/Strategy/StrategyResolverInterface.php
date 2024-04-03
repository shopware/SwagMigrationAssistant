<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media\Strategy;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
interface StrategyResolverInterface
{
    public function supports(string $path, MigrationContextInterface $migrationContext): bool;

    public function resolve(string $path, MigrationContextInterface $migrationContext): string;
}
