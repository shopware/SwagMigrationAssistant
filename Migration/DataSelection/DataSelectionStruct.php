<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use Shopware\Core\Framework\Struct\Struct;

class DataSelectionStruct extends Struct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string[]
     */
    protected $entityNames;

    /**
     * @var string
     */
    protected $snippet;

    /**
     * @var int
     */
    protected $position;

    public function __construct(string $id, array $entityName, string $snippet, int $position)
    {
        $this->id = $id;
        $this->entityNames = $entityName;
        $this->snippet = $snippet;
        $this->position = $position;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityNames(): array
    {
        return $this->entityNames;
    }

    public function getSnippet(): string
    {
        return $this->snippet;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
