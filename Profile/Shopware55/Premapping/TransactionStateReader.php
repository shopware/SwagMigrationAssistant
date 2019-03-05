<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Premapping;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationNext\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\Gateway\TableReaderFactory;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class TransactionStateReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'transaction_state';

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
            && in_array('customersOrders', $entityGroupNames, true);
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
            if ($data['group'] === 'payment') {
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
        $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATE_MACHINE));

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
            case 9: // partially_invoiced
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 10: // completely_invoiced
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 11: // partially_paid
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_PARTIALLY_PAID];
                break;
            case 12: // completely_paid
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_PAID];
                break;
            case 13: // 1st_reminder
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_REMINDED];
                break;
            case 14: // 2nd_reminder
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_REMINDED];
                break;
            case 15: // 3rd_reminder
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_REMINDED];
                break;
            case 16: // encashment
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_REMINDED];
                break;
            case 17: // open
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 18: // reserved
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 19: // delayed
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 20: // re_crediting
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_REFUNDED];
                break;
            case 21: // review_necessary
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 30: // no_credit_approved
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 31: // the_credit_has_been_preliminarily_accepted
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 32: // the_credit_has_been_accepted
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 33: // the_payment_has_been_ordered
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 34: // a_time_extension_has_been_registered
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_OPEN];
                break;
            case 35: // the_process_has_been_cancelled
            case 0: // Cancelled order without payment state
                $preselectionValue = $this->preselectionDictionary[Defaults::ORDER_TRANSACTION_STATES_CANCELLED];
                break;
        }

        return $preselectionValue;
    }
}
