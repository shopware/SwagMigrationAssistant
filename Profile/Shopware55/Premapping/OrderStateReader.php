<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Premapping;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\TableReaderFactory;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class OrderStateReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'order_state';

    /**
     * @var EntityRepositoryInterface
     */
    protected $stateMachineRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $stateMachineStateRepo;

    /**
     * @var string[]
     */
    protected $preselectionDictionary = [];

    public function __construct(
        EntityRepositoryInterface $stateMachineRepo,
        EntityRepositoryInterface $stateMachineStateRepo
    ) {
        $this->stateMachineRepo = $stateMachineRepo;
        $this->stateMachineStateRepo = $stateMachineStateRepo;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME
            && in_array(CustomerAndOrderDataSelection::IDENTIFIER, $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingDictionary($migrationContext);
        $mapping = $this->getMapping($migrationContext);
        $choices = $this->getChoices($context);
        $this->setPreselection($mapping);

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(MigrationContext $migrationContext): array
    {
        $readerFactory = new TableReaderFactory();
        $reader = $readerFactory->create($migrationContext);

        if ($reader === null) {
            return [];
        }

        $preMappingData = $reader->read('s_core_states');

        $entityData = [];
        foreach ($preMappingData as $data) {
            if ($data['group'] === 'state') {
                $uuid = '';
                if (isset($this->connectionPremappingDictionary[$data['id']])) {
                    $uuid = $this->connectionPremappingDictionary[$data['id']]['destinationUuid'];
                }

                $entityData[] = new PremappingEntityStruct($data['id'], $data['description'], $uuid);
            }
        }

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_MACHINE));

        /** @var StateMachineEntity $stateMachine */
        $stateMachine = $this->stateMachineRepo->search($criteria, $context)->first();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineId', $stateMachine->getId()));
        $criteria->addSorting(new FieldSorting('name'));
        $states = $this->stateMachineStateRepo->search($criteria, $context);

        $choices = [];
        /** @var StateMachineStateEntity $state */
        foreach ($states as $state) {
            $this->preselectionDictionary[$state->getTechnicalName()] = $state->getId();
            $choices[] = new PremappingChoiceStruct($state->getId(), $state->getName());
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
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_CANCELLED];
                break;
            case 0: // open
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_OPEN];
                break;
            case 1: // in_process
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_OPEN];
                break;
            case 2: // completed
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_SHIPPED];
                break;
            case 3: // partially_completed
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_PARTIALLY_SHIPPED];
                break;
            case 4: // cancelled_rejected
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_OPEN];
                break;
            case 5: // ready_for_delivery
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_OPEN];
                break;
            case 6: // partially_delivered
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_PARTIALLY_SHIPPED];
                break;
            case 7: // completely_delivered
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_SHIPPED];
                break;
            case 8: // clarification_required
                $preselectionValue = $this->preselectionDictionary[OrderDeliveryStates::STATE_OPEN];
                break;
        }

        return $preselectionValue;
    }
}
