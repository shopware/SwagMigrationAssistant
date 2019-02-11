<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Mapping;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationNext\Migration\Mapping\MappingService;

class Shopware55MappingService extends MappingService implements Shopware55MappingServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $stateMachineRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $stateMachineStateRepository;

    public function __construct(
        EntityRepositoryInterface $migrationMappingRepo,
        EntityRepositoryInterface $localeRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $stateMachineRepository,
        EntityRepositoryInterface $stateMachineStateRepository,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $salesChannelRepo,
        EntityRepositoryInterface $salesChannelTypeRepo
    ) {
        parent::__construct(
            $migrationMappingRepo,
            $localeRepository,
            $languageRepository,
            $countryRepository,
            $currencyRepository,
            $salesChannelRepo,
            $salesChannelTypeRepo
        );

        $this->paymentRepository = $paymentRepository;
        $this->stateMachineRepository = $stateMachineRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
    }

    public function getPaymentUuid(string $technicalName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->setLimit(1);
        $result = $this->paymentRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var PaymentMethodEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function getOrderStateUuid(int $oldStateId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_MACHINE));

        /** @var StateMachineEntity $stateMachine */
        $stateMachine = $this->stateMachineRepository->search($criteria, $context)->first();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineId', $stateMachine->getId()));
        $criteria->setLimit(1);
        switch ($oldStateId) {
            case -1: // cancelled
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_CANCELLED));
                break;
            case 0: // open
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_OPEN));
                break;
            case 1: // in_process
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_IN_PROGRESS));
                break;
            case 2: // completed
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_COMPLETED));
                break;
            case 3: // partially_completed
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_IN_PROGRESS));
                break;
            case 4: // cancelled_rejected
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_OPEN));
                break;
            case 5: // ready_for_delivery
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_IN_PROGRESS));
                break;
            case 6: // partially_delivered
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_IN_PROGRESS));
                break;
            case 7: // completely_delivered
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_IN_PROGRESS));
                break;
            case 8: // clarification_required
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_STATE_STATES_IN_PROGRESS));
                break;
            default:
                return null;
        }

        $result = $this->stateMachineStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var StateMachineStateEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function getTransactionStateUuid(int $oldStateId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATE_MACHINE));

        /** @var StateMachineEntity $stateMachine */
        $stateMachine = $this->stateMachineRepository->search($criteria, $context)->first();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineId', $stateMachine->getId()));
        $criteria->setLimit(1);
        switch ($oldStateId) {
            case 9: // partially_invoiced
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 10: // completely_invoiced
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 11: // partially_paid
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_PARTIALLY_PAID));
                break;
            case 12: // completely_paid
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_PAID));
                break;
            case 13: // 1st_reminder
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_REMINDED));
                break;
            case 14: // 2nd_reminder
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_REMINDED));
                break;
            case 15: // 3rd_reminder
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_REMINDED));
                break;
            case 16: // encashment
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_REMINDED));
                break;
            case 17: // open
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 18: // reserved
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 19: // delayed
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 20: // re_crediting
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_REFUNDED));
                break;
            case 21: // review_necessary
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 30: // no_credit_approved
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 31: // the_credit_has_been_preliminarily_accepted
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 32: // the_credit_has_been_accepted
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 33: // the_payment_has_been_ordered
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 34: // a_time_extension_has_been_registered
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_OPEN));
                break;
            case 35: // the_process_has_been_cancelled
            case 0: // Cancelled order without payment state
                $criteria->addFilter(new EqualsFilter('technicalName', Defaults::ORDER_TRANSACTION_STATES_CANCELLED));
                break;
            default:
                return null;
        }

        $result = $this->stateMachineStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var StateMachineStateEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
