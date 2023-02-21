<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

class CleanupMigrationMessage
{
    public function __construct(private readonly ?string $tableName = null)
    {
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }
}
