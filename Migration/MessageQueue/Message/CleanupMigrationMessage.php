<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

class CleanupMigrationMessage
{
    /**
     * @var string|null
     */
    private $tableName;

    public function __construct(?string $tableName = null)
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }
}
