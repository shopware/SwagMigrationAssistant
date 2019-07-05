<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;

class SwagMigrationProfileEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var SwagMigrationConnectionCollection|null
     */
    protected $connections;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getConnections(): ?SwagMigrationConnectionCollection
    {
        return $this->connections;
    }

    public function setConnections(SwagMigrationConnectionCollection $connections): void
    {
        $this->connections = $connections;
    }
}
