<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\StateMachineCollection;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class OrderStateReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'order_state';

    /**
     * @var array<string>
     */
    protected array $preselectionDictionary = [];

    /**
     * @var array<string, string>
     */
    private array $choiceUuids = [];

    /**
     * @param EntityRepository<StateMachineCollection> $stateMachineRepo
     * @param EntityRepository<StateMachineStateCollection> $stateMachineStateRepo
     */
    public function __construct(
        private readonly EntityRepository $stateMachineRepo,
        private readonly EntityRepository $stateMachineStateRepo,
        private readonly GatewayRegistryInterface $gatewayRegistry
    ) {
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
        $this->fillConnectionPremappingDictionary($migrationContext);
        $choices = $this->getChoices($context);
        $mapping = $this->getMapping($migrationContext);
        $this->setPreselection($mapping);

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(MigrationContextInterface $migrationContext): array
    {
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);

        if (!$gateway instanceof ShopwareGatewayInterface) {
            return [];
        }

        $preMappingData = $gateway->readTable($migrationContext, 's_core_states');

        $entityData = [];
        foreach ($preMappingData as $data) {
            if ($data['group'] === 'state') {
                $uuid = '';
                if (isset($this->connectionPremappingDictionary[$data['id']])) {
                    $uuid = $this->connectionPremappingDictionary[$data['id']]->getDestinationUuid();

                    if (!isset($this->choiceUuids[$uuid])) {
                        $uuid = '';
                    }
                }
                if (!empty($data['description'])) {
                    $description = $data['description'];
                } elseif (!empty($data['name'])) {
                    $description = $data['name'];
                } else {
                    $description = $data['id'];
                }

                $entityData[] = new PremappingEntityStruct($data['id'], $description, $uuid);
            }
        }
        \usort($entityData, function (PremappingEntityStruct $item1, PremappingEntityStruct $item2) {
            return \strcmp($item1->getDescription(), $item2->getDescription());
        });

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderStates::STATE_MACHINE));

        $stateMachine = $this->stateMachineRepo->search($criteria, $context)->first();

        if (!$stateMachine instanceof StateMachineEntity) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineId', $stateMachine->getId()));
        $criteria->addSorting(new FieldSorting('name'));
        $states = $this->stateMachineStateRepo->search($criteria, $context)->getEntities();

        $choices = [];
        foreach ($states as $state) {
            $id = $state->getId();
            $this->preselectionDictionary[$state->getTechnicalName()] = $id;
            $choices[] = new PremappingChoiceStruct($id, $state->getName());
            $this->choiceUuids[$id] = $id;
        }

        return $choices;
    }

    /**
     * @param PremappingEntityStruct[] $mapping
     */
    private function setPreselection(array $mapping): void
    {
        foreach ($mapping as $item) {
            if ($item->getDestinationUuid() !== '') {
                continue;
            }

            $preselectionValue = $this->getPreselectionValue($item->getSourceId());

            if ($preselectionValue !== null) {
                $item->setDestinationUuid($preselectionValue);
            }
        }
    }

    private function getPreselectionValue(string $sourceId): ?string
    {
        $preselectionValue = null;

        switch ($sourceId) {
            case -1: // cancelled
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_CANCELLED] ?? null;

                break;
            case 0: // open
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_OPEN] ?? null;

                break;
            case 1: // in_process
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_IN_PROGRESS] ?? null;

                break;
            case 2: // completed
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_COMPLETED] ?? null;

                break;
            case 3: // partially_completed
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_IN_PROGRESS] ?? null;

                break;
            case 4: // cancelled_rejected
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_CANCELLED] ?? null;

                break;
            case 5: // ready_for_delivery
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_IN_PROGRESS] ?? null;

                break;
            case 6: // partially_delivered
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_IN_PROGRESS] ?? null;

                break;
            case 7: // completely_delivered
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_IN_PROGRESS] ?? null;

                break;
            case 8: // clarification_required
                $preselectionValue = $this->preselectionDictionary[OrderStates::STATE_IN_PROGRESS] ?? null;

                break;
        }

        return $preselectionValue;
    }
}
