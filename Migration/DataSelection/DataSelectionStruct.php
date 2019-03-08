<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use Shopware\Core\Framework\Struct\Struct;

class DataSelectionStruct extends Struct
{
    public const BASIC_DATA_TYPE = 'basicData';
    public const PLUGIN_DATA_TYPE = 'basicPluginData';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string[]
     */
    protected $entityNames;

    /**
     * @var bool
     */
    protected $processMediaFiles;

    /**
     * @var string
     */
    protected $snippet;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string
     */
    protected $dataType;

    public function __construct(
        string $id,
        array $entityName,
        string $snippet,
        int $position,
        bool $processMediaFiles = false,
        string $dataType = self::BASIC_DATA_TYPE
    ) {
        $this->id = $id;
        $this->entityNames = $entityName;
        $this->snippet = $snippet;
        $this->position = $position;
        $this->processMediaFiles = $processMediaFiles;
        $this->dataType = $dataType;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityNames(): array
    {
        return $this->entityNames;
    }

    public function getProcessMediaFiles(): bool
    {
        return $this->processMediaFiles;
    }

    public function getSnippet(): string
    {
        return $this->snippet;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }
}
