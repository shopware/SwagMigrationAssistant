<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test;

use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverter;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MediaConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55TranslationConverter;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Profile\Dummy\DummyInvalidCustomerConverter;
use Symfony\Component\Validator\Validation;

trait MigrationServicesTrait
{
    protected function getMigrationDataFetcher(
        EntityWriterInterface $entityWriter,
        MappingService $mappingService,
        MediaFileServiceInterface $mediaFileService,
        EntityRepositoryInterface $loggingRepo,
        EntityDefinition $dataDefinition,
        DataSetRegistryInterface $dataSetRegistry,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $languageRepository,
        ReaderRegistryInterface $readerRegistry
    ): MigrationDataFetcherInterface {
        $loggingService = new LoggingService($loggingRepo);

        $connectionFactory = new ConnectionFactory();
        $gatewayRegistry = new GatewayRegistry(new DummyCollection([
            new ShopwareApiGateway(
                $readerRegistry,
                new EnvironmentReader($connectionFactory),
                new TableReader($connectionFactory),
                new TableCountReader($connectionFactory, $loggingService),
                $currencyRepository,
                $languageRepository
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
        EntityDefinition $dataDefinition,
        EntityRepositoryInterface $paymentRepo,
        EntityRepositoryInterface $shippingRepo,
        EntityRepositoryInterface $countryRepo,
        EntityRepositoryInterface $salesChannelRepo
    ): MigrationDataConverterInterface {
        $loggingService = new LoggingService($loggingRepo);
        $validator = Validation::createValidator();
        $converterRegistry = new ConverterRegistry(
            new DummyCollection(
                [
                    new Shopware55ProductConverter($mappingService, $loggingService, $mediaFileService),
                    new Shopware55TranslationConverter($mappingService, $loggingService),
                    new Shopware55CategoryConverter($mappingService, $loggingService, $mediaFileService),
                    new Shopware55MediaConverter($mappingService, $loggingService, $mediaFileService),
                    new Shopware55CustomerConverter($mappingService, $loggingService, $validator),
                    new Shopware55CustomerConverter($mappingService, $loggingService, $validator),
                    new Shopware55OrderConverter(
                        $mappingService,
                        $loggingService,
                        new TaxCalculator()
                    ),
                    new Shopware55SalesChannelConverter($mappingService, $loggingService, $paymentRepo, $shippingRepo, $countryRepo, $salesChannelRepo, null),
                    new DummyInvalidCustomerConverter($mappingService, $loggingService, $validator),
                ]
            )
        );

        $migrationDataConverter = new MigrationDataConverter(
            $entityWriter,
            $converterRegistry,
            $mediaFileService,
            $loggingService,
            $dataDefinition,
            new DummyMappingService()
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

    private function getCategoryUuid(EntityRepositoryInterface $categoryRepo, Context $context): string
    {
        /** @var CategoryEntity $category */
        $category = $categoryRepo->search(new Criteria(), $context)->first();

        return $category->getId();
    }
}
