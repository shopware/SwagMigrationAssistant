<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use Shopware\Core\Framework\Struct\Struct;

class CountingInformationStruct extends Struct
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var CountingQueryCollection
     */
    protected $queries;

    public function __construct(string $entityName)
    {
        $this->entityName = $entityName;
        $this->queries = new CountingQueryCollection();
    }

    public function addQueryStruct(CountingQueryStruct $struct): void
    {
        $this->queries->add($struct);
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getQueries(): CountingQueryCollection
    {
        return $this->queries;
    }
}
