<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\Struct\Struct;

class MappingDeltaResult extends Struct
{
    /**
     * @var array
     */
    private $migrationData;

    /**
     * @var array
     */
    private $preloadIds;

    public function __construct(array $migrationData = [], array $preloadIds = [])
    {
        $this->migrationData = $migrationData;
        $this->preloadIds = $preloadIds;
    }

    public function getMigrationData(): array
    {
        return $this->migrationData;
    }

    public function getPreloadIds(): array
    {
        return $this->preloadIds;
    }
}
