<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Gateway\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface EnvironmentReaderInterface
{
    public function read(MigrationContextInterface $migrationContext): array;
}
