<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\ORM\Entity;
use Shopware\Core\Framework\Struct\Struct;

class ConvertStruct extends Struct
{
    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var array
     */
    private $unmapped;

    public function __construct(Entity $entity, array $unmapped)
    {
        $this->entity = $entity;
        $this->unmapped = $unmapped;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getUnmapped(): array
    {
        return $this->unmapped;
    }
}
