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

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $oldId;

    public function __construct(array $converted, array $unmapped, string $oldId, string $uuid)
    {
        $this->converted = $converted;
        $this->unmapped = $unmapped;
        $this->uuid = $uuid;
        $this->oldId = $oldId;
    }

    public function getConverted(): array
    {
        return $this->converted;
    }

    public function getUnmapped(): array
    {
        return $this->unmapped;
    }

    public function getOldId(): string
    {
        return $this->oldId;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }
}
