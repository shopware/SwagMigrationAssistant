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
class PremappingStruct extends Struct
{
    /**
     * @param array<PremappingEntityStruct> $mapping
     * @param array<PremappingChoiceStruct> $choices
     */
    public function __construct(
        protected string $entity,
        protected array $mapping,
        protected array $choices = []
    ) {
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @return PremappingEntityStruct[]
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }
}
