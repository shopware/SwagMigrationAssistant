<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MigrationFix;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;

#[Package('after-sales')]
class SwagMigrationFixEntity extends Entity
{
    use EntityIdTrait;

    protected string $connectionId;

    protected string $mainMappingId;

    protected string $value;

    protected string $path;

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function setConnectionId(string $connectionId): void
    {
        $this->connectionId = $connectionId;
    }

    public function getMainMappingId(): string
    {
        return $this->mainMappingId;
    }

    public function setMainMappingId(string $mainMappingId): void
    {
        $this->mainMappingId = $mainMappingId;
    }

    public function getValue(): mixed
    {
        return \json_decode($this->value, true, 512, \JSON_THROW_ON_ERROR);
    }

    public function setValue(mixed $value): void
    {
        $this->value = \json_encode($value, \JSON_THROW_ON_ERROR);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }
}
