<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Converter;

use Shopware\Core\Framework\Struct\Struct;

class ConvertStruct extends Struct
{
    /**
     * @var array|null
     */
    private $converted;

    /**
     * @var array|null
     */
    private $unmapped;

    /**
     * Represents the mapping id of the entity which gets converted.
     *
     * @var string|null
     */
    private $mappingUuid;

    public function __construct(?array $converted, ?array $unmapped, ?string $mappingUuid = null)
    {
        $this->converted = $converted;
        $this->unmapped = $unmapped;
        $this->mappingUuid = $mappingUuid;
    }

    public function getConverted(): ?array
    {
        return $this->converted;
    }

    public function getUnmapped(): ?array
    {
        return $this->unmapped;
    }

    public function getMappingUuid(): ?string
    {
        return $this->mappingUuid;
    }
}
