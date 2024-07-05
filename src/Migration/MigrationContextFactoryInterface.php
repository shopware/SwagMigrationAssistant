<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
interface MigrationContextFactoryInterface
{
    public function create(SwagMigrationRunEntity $run, int $offset = 0, int $limit = 0, string $entity = ''): ?MigrationContextInterface;

    public function createByProfileName(string $profileName): MigrationContextInterface;

    public function createByConnection(SwagMigrationConnectionEntity $connection): MigrationContextInterface;

    public function createBySelectedConnection(Context $context): MigrationContextInterface;
}
