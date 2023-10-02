<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;

#[Package('services-settings')]
class GeneralSettingEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $selectedConnectionId;

    protected ?SwagMigrationConnectionEntity $selectedConnection;

    protected bool $isReset;

    public function getSelectedConnectionId(): ?string
    {
        return $this->selectedConnectionId;
    }

    public function setSelectedConnectionId(string $selectedConnectionId): void
    {
        $this->selectedConnectionId = $selectedConnectionId;
    }

    public function getSelectedConnection(): ?SwagMigrationConnectionEntity
    {
        return $this->selectedConnection;
    }

    public function setSelectedConnection(SwagMigrationConnectionEntity $selectedConnection): void
    {
        $this->selectedConnection = $selectedConnection;
    }

    public function isReset(): bool
    {
        return $this->isReset;
    }

    public function setIsReset(bool $isReset): void
    {
        $this->isReset = $isReset;
    }
}
