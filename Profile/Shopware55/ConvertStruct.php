<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Struct\Struct;

class ConvertStruct extends Struct
{
    /**
     * @var array
     */
    private $converted;

    /**
     * @var array
     */
    private $unmapped;

    public function __construct(?array $converted, ?array $unmapped)
    {
        $this->converted = $converted;
        $this->unmapped = $unmapped;
    }

    public function getConverted(): ?array
    {
        return $this->converted;
    }

    public function getUnmapped(): ?array
    {
        return $this->unmapped;
    }
}
