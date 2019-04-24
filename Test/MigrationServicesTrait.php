<?php declare(strict_types=1);

namespace SwagMigrationNext\Test;

use Shopware\Core\Checkout\Cart\Price\PriceRounding;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxRuleCalculator;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Service\MigrationDataFetcher;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\MediaConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\OrderConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Profile\Dummy\DummyInvalidCustomerConverter;

trait MigrationServicesTrait
{
    protected function getMigrationDataFetcher(
        EntityWriterInterface $entityWriter,
        MappingService $mappingService,
        MediaFileServiceInterface $mediaFileService,
        EntityRepositoryInterface $loggingRepo
    ): MigrationDataFetcherInterface {
        $loggingService = new LoggingService($loggingRepo);
        $priceRounding = new PriceRounding();
        $converterRegistry = new ConverterRegistry(
            new DummyCollection(
                [
                    new ProductConverter($mappingService, $mediaFileService, $loggingService),
                    new TranslationConverter($mappingService, $loggingService),
                    new CategoryConverter($mappingService, $loggingService),
                    new MediaConverter($mappingService, $mediaFileService),
                    new CustomerConverter($mappingService, $loggingService),
                    new CustomerConverter($mappingService, $loggingService),
                    new OrderConverter(
                        $mappingService,
                        new TaxCalculator(
                            $priceRounding,
                            new TaxRuleCalculator($priceRounding)
                        ),
                        $loggingService
                    ),
                    new DummyInvalidCustomerConverter($mappingService, $loggingService),
                ]
            )
        );

        $profileRegistry = new ProfileRegistry(new DummyCollection([
            new Shopware55Profile(
                $entityWriter,
                $converterRegistry,
                $mediaFileService,
                $loggingService
            ),
        ]));

        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        return new MigrationDataFetcher($profileRegistry, $gatewayFactoryRegistry, $loggingService);
    }

    protected function getOrderStateUuid(
        EntityRepositoryInterface $stateMachineRepository,
        EntityRepositoryInterface $stateMachineStateRepository,
        int $oldStateId,
        Context $context
    ): ?string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_MACHINE));

        /** @var StateMachineEntity $stateMachine */
        $stateMachine = $stateMachineRepository->search($criteria, $context)->first();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineId', $stateMachine->getId()));
        $criteria->setLimit(1);
        switch ($oldStateId) {
            case -1: // cancelled
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_CANCELLED));
                break;
            case 0: // open
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_OPEN));
                break;
            case 1: // in_process
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_OPEN));
                break;
            case 2: // completed
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_SHIPPED));
                break;
            case 3: // partially_completed
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_PARTIALLY_SHIPPED));
                break;
            case 4: // cancelled_rejected
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_OPEN));
                break;
            case 5: // ready_for_delivery
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_OPEN));
                break;
            case 6: // partially_delivered
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_PARTIALLY_SHIPPED));
                break;
            case 7: // completely_delivered
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_SHIPPED));
                break;
            case 8: // clarification_required
                $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_OPEN));
                break;
            default:
                return null;
        }

        $result = $stateMachineStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var StateMachineStateEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    protected function getTransactionStateUuid(
        EntityRepositoryInterface $stateMachineRepository,
        EntityRepositoryInterface $stateMachineStateRepository,
        int $oldStateId,
        Context $context
    ): ?string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));

        /** @var StateMachineEntity $stateMachine */
        $stateMachine = $stateMachineRepository->search($criteria, $context)->first();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('stateMachineId', $stateMachine->getId()));
        $criteria->setLimit(1);
        switch ($oldStateId) {
            case 9: // partially_invoiced
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 10: // completely_invoiced
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 11: // partially_paid
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_PARTIALLY_PAID));
                break;
            case 12: // completely_paid
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_PAID));
                break;
            case 13: // 1st_reminder
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_REMINDED));
                break;
            case 14: // 2nd_reminder
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_REMINDED));
                break;
            case 15: // 3rd_reminder
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_REMINDED));
                break;
            case 16: // encashment
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_REMINDED));
                break;
            case 17: // open
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 18: // reserved
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 19: // delayed
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 20: // re_crediting
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_REFUNDED));
                break;
            case 21: // review_necessary
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 30: // no_credit_approved
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 31: // the_credit_has_been_preliminarily_accepted
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 32: // the_credit_has_been_accepted
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 33: // the_payment_has_been_ordered
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 34: // a_time_extension_has_been_registered
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_OPEN));
                break;
            case 35: // the_process_has_been_cancelled
            case 0: // Cancelled order without payment state
                $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_CANCELLED));
                break;
            default:
                return null;
        }

        $result = $stateMachineStateRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var StateMachineStateEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    private function getPaymentUuid(EntityRepositoryInterface $paymentRepo, string $technicalName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $technicalName));
        $criteria->setLimit(1);
        $result = $paymentRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var PaymentMethodEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    private function getSalutationUuid(EntityRepositoryInterface $salutationRepo, string $salutationKey, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
        $criteria->setLimit(1);
        $result = $salutationRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SalutationEntity $salutation */
            $salutation = $result->getEntities()->first();

            return $salutation->getId();
        }

        return null;
    }

    private function getFirstDeliveryTimeUuid(EntityRepositoryInterface $deliveryTimeRepo, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));
        $criteria->setLimit(1);
        $result = $deliveryTimeRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var DeliveryTimeEntity $deliveryTime */
            $deliveryTime = $result->getEntities()->first();

            return $deliveryTime->getId();
        }

        return null;
    }
}
