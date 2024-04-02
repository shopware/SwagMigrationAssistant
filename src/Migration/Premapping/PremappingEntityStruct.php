<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Premapping;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class PremappingEntityStruct extends Struct
{
    public function __construct(
        protected string $sourceId,
        protected string $description,
        protected string $destinationUuid
    ) {
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
