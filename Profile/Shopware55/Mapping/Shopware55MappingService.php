<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Mapping;

use Shopware\Core\Checkout\Order\Aggregate\OrderState\OrderStateStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionState\OrderTransactionStateStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\TermQuery;
use SwagMigrationNext\Migration\Asset\MediaFileServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingService;

class Shopware55MappingService extends MappingService
{
    /**
     * @var RepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var RepositoryInterface
     */
    private $orderStateRepository;

    /**
     * @var RepositoryInterface
     */
    private $transactionStateRepository;

    public function __construct(
        RepositoryInterface $migrationMappingRepo,
        RepositoryInterface $localeRepository,
        RepositoryInterface $languageRepository,
        RepositoryInterface $countryRepository,
        RepositoryInterface $paymentRepository,
        RepositoryInterface $orderStateRepository,
        RepositoryInterface $transactionStateRepository,
        RepositoryInterface $currencyRepository,
        MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($migrationMappingRepo, $localeRepository, $languageRepository, $countryRepository, $currencyRepository, $mediaFileService);

        $this->paymentRepository = $paymentRepository;
        $this->orderStateRepository = $orderStateRepository;
        $this->transactionStateRepository = $transactionStateRepository;
    }

    public function getPaymentUuid(string $technicalName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->setLimit(1);
        $result = $this->paymentRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var PaymentMethodStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function getOrderStateUuid(int $oldStateId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        switch ($oldStateId) {
            case -1: // cancelled
                $criteria->addFilter(new EqualsFilter('position', 3));
                break;
            case 0: // open
                $criteria->addFilter(new EqualsFilter('position', 1));
                break;
            case 1: // in_process
                $criteria->addFilter(new EqualsFilter('position', 4));
                break;
            case 2: // completed
                $criteria->addFilter(new EqualsFilter('position', 2));
                break;
            case 3: // partially_completed
                $criteria->addFilter(new EqualsFilter('position', 5));
                break;
            case 4: // cancelled_rejected
                $criteria->addFilter(new EqualsFilter('position', 6));
                break;
            case 5: // ready_for_delivery
                $criteria->addFilter(new EqualsFilter('position', 7));
                break;
            case 6: // partially_delivered
                $criteria->addFilter(new EqualsFilter('position', 8));
                break;
            case 7: // completely_delivered
                $criteria->addFilter(new EqualsFilter('position', 9));
                break;
            case 8: // clarification_required
                $criteria->addFilter(new EqualsFilter('position', 10));
                break;
            default:
                return null;
        }

        $result = $this->orderStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var OrderStateStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function getTransactionStateUuid(int $oldStateId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        switch ($oldStateId) {
            case 9: // partially_invoiced
                $criteria->addFilter(new EqualsFilter('position', 4));
                break;
            case 10: // completely_invoiced
                $criteria->addFilter(new EqualsFilter('position', 5));
                break;
            case 11: // partially_paid
                $criteria->addFilter(new EqualsFilter('position', 6));
                break;
            case 12: // completely_paid
                $criteria->addFilter(new EqualsFilter('position', 7));
                break;
            case 13: // 1st_reminder
                $criteria->addFilter(new EqualsFilter('position', 8));
                break;
            case 14: // 2nd_reminder
                $criteria->addFilter(new EqualsFilter('position', 9));
                break;
            case 15: // 3rd_reminder
                $criteria->addFilter(new EqualsFilter('position', 10));
                break;
            case 16: // encashment
                $criteria->addFilter(new EqualsFilter('position', 11));
                break;
            case 17: // open
                $criteria->addFilter(new EqualsFilter('position', 3));
                break;
            case 18: // reserved
                $criteria->addFilter(new EqualsFilter('position', 12));
                break;
            case 19: // delayed
                $criteria->addFilter(new EqualsFilter('position', 13));
                break;
            case 20: // re_crediting
                $criteria->addFilter(new EqualsFilter('position', 14));
                break;
            case 21: // review_necessary
                $criteria->addFilter(new EqualsFilter('position', 15));
                break;
            case 30: // no_credit_approved
                $criteria->addFilter(new EqualsFilter('position', 16));
                break;
            case 31: // the_credit_has_been_preliminarily_accepted
                $criteria->addFilter(new EqualsFilter('position', 17));
                break;
            case 32: // the_credit_has_been_accepted
                $criteria->addFilter(new EqualsFilter('position', 18));
                break;
            case 33: // the_payment_has_been_ordered
                $criteria->addFilter(new EqualsFilter('position', 19));
                break;
            case 34: // a_time_extension_has_been_registered
                $criteria->addFilter(new EqualsFilter('position', 20));
                break;
            case 35: // the_process_has_been_cancelled
            case 0: // Cancelled order without payment state
                $criteria->addFilter(new EqualsFilter('position', 2));
                break;
            default:
                return null;
        }

        $result = $this->transactionStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var OrderTransactionStateStruct $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
