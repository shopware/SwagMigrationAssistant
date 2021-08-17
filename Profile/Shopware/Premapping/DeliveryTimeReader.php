<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class DeliveryTimeReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'delivery_time';

    /**
     * @var string
     */
    private $connectionPremappingValue = '';

    /**
     * @var EntityRepositoryInterface
     */
    private $deliveryTimeRepo;

    /**
     * @var string[]
     */
    private $choiceUuids;

    public function __construct(EntityRepositoryInterface $deliveryTimeRepo)
    {
        $this->deliveryTimeRepo = $deliveryTimeRepo;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext, array $entityGroupNames): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && \in_array(CustomerAndOrderDataSelection::IDENTIFIER, $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContextInterface $migrationContext): PremappingStruct
    {
        $choices = $this->getChoices($context);
        $this->fillConnectionPremappingValue($migrationContext);
        $mapping = $this->getMapping();

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    protected function fillConnectionPremappingValue(MigrationContextInterface $migrationContext): void
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return;
        }

        $mappingArray = $connection->getPremapping();

        if ($mappingArray === null) {
            return;
        }

        foreach ($mappingArray as $premapping) {
            if ($premapping['entity'] === self::MAPPING_NAME) {
                foreach ($premapping['mapping'] as $mapping) {
                    if (isset($this->choiceUuids[$mapping['destinationUuid']])) {
                        $this->connectionPremappingValue = $mapping['destinationUuid'];
                    }
                }
            }
        }
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(): array
    {
        $entityData = [];
        $entityData[] = new PremappingEntityStruct('default_delivery_time', 'Standard delivery time', $this->connectionPremappingValue);

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));
        $deliveryTimes = $this->deliveryTimeRepo->search($criteria, $context);

        $choices = [];
        /** @var DeliveryTimeEntity $deliveryTime */
        foreach ($deliveryTimes as $deliveryTime) {
            $id = $deliveryTime->getId();
            $name = $deliveryTime->getName() ?? '';
            $choices[] = new PremappingChoiceStruct($id, $name);
            $this->choiceUuids[$id] = $id;
        }

        return $choices;
    }
}
