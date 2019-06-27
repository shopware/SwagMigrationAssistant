<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use Shopware\Core\Framework\Struct\Struct;

class CountingQueryStruct extends Struct
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string|null
     */
    private $condition;

    public function __construct(string $tableName, ?string $condition = null)
    {
        $this->tableName = $tableName;
        $this->condition = $condition;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }
}
