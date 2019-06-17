<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test;

use Shopware\Core\Checkout\Cart\Price\PriceRounding;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxRuleCalculator;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverter;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\MediaConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\OrderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiTableReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;
use SwagMigrationAssistant\Test\Mock\Profile\Dummy\DummyInvalidCustomerConverter;

trait MigrationServicesTrait
{
    protected function getMigrationDataFetcher(
        EntityWriterInterface $entityWriter,
        MappingService $mappingService,
        MediaFileServiceInterface $mediaFileService,
        EntityRepositoryInterface $loggingRepo,
        EntityDefinition $dataDefinition
    ): MigrationDataFetcherInterface {
        $loggingService = new LoggingService($loggingRepo);
        $priceRounding = new PriceRounding();

        $connectionFactory = new ConnectionFactory();
        $gatewayRegistry = new GatewayRegistry(new DummyCollection([
            new Shopware55ApiGateway(
                new Shopware55ApiReader($connectionFactory),
                new Shopware55ApiEnvironmentReader($connectionFactory),
                new Shopware55ApiTableReader($connectionFactory)
            ),
            new DummyLocalGateway(),
        ]));

        return new MigrationDataFetcher($gatewayRegistry, $loggingService);
    }

    protected function getMigrationDataConverter(
        EntityWriterInterface $entityWriter,
        MappingService $mappingService,
        MediaFileServiceInterface $mediaFileService,
        EntityRepositoryInterface $loggingRepo,
        EntityDefinition $dataDefinition
    ): MigrationDataConverterInterface {
        $priceRounding = new PriceRounding();
        $loggingService = new LoggingService($loggingRepo);
        $converterRegistry = new ConverterRegistry(
            new DummyCollection(
                [
                    new ProductConverter($mappingService, $mediaFileService, $loggingService),
                    new TranslationConverter($mappingService, $loggingService),
                    new CategoryConverter($mappingService, $mediaFileService, $loggingService),
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

        $migrationDataConverter = new MigrationDataConverter(
            $entityWriter,
            $converterRegistry,
            $mediaFileService,
            $loggingService,
            $dataDefinition
        );

        return $migrationDataConverter;
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

    private function getCurrencyUuid(EntityRepositoryInterface $currencyRepo, string $isoCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $isoCode));
        $criteria->setLimit(1);
        $result = $currencyRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CurrencyEntity $currency */
            $currency = $result->getEntities()->first();

            return $currency->getId();
        }

        return null;
    }

    private function getLanguageUuid(EntityRepositoryInterface $localeRepo, EntityRepositoryInterface $languageRepo, string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $code));
        $criteria->setLimit(1);
        /** @var LocaleEntity $result */
        $result = $localeRepo->search($criteria, $context)->first();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('localeId', $result->getId()));
        $result = $languageRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LanguageEntity $language */
            $language = $result->getEntities()->first();

            return $language->getId();
        }

        return null;
    }
}
