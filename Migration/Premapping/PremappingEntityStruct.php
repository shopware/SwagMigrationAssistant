<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Premapping;

use Shopware\Core\Framework\Struct\Struct;

class PremappingEntityStruct extends Struct
{
    /**
     * @var string
     */
    protected $sourceId;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $destinationUuid;

    public function __construct(string $sourceId, string $description, string $destinationUuid)
    {
        $this->sourceId = $sourceId;
        $this->description = $description;
        $this->destinationUuid = $destinationUuid;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDestinationUuid(): string
    {
        return $this->destinationUuid;
    }

    public function setDestinationUuid(string $destinationUuid): void
    {
        $this->destinationUuid = $destinationUuid;
    }
}
