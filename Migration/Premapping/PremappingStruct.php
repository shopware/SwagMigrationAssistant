<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Premapping;

use Shopware\Core\Framework\Struct\Struct;

class PremappingStruct extends Struct
{
    /**
     * @var string
     */
    protected $entity;

    /**
     * @var PremappingEntityStruct[]
     */
    protected $mapping;

    /**
     * @var PremappingChoiceStruct[]
     */
    protected $choices;

    public function __construct(string $entity, array $mapping, array $choices = [])
    {
        $this->entity = $entity;
        $this->mapping = $mapping;
        $this->choices = $choices;
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
