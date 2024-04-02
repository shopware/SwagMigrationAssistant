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
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\StateMachineCollection;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
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

#[Package('services-settings')]
trait MigrationServicesTrait
{
    /**
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     * @param EntityRepository<CurrencyCollection> $currencyRepository
     * @param EntityRepository<LanguageCollection> $languageRepository
     */
    protected function getMigrationDataFetcher(
        EntityRepository $loggingRepo,
        EntityRepository $currencyRepository,
        EntityRepository $languageRepository,
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

    /**
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     * @param EntityRepository<PaymentMethodCollection> $paymentRepo
     * @param EntityRepository<ShippingMethodCollection> $shippingRepo
     * @param EntityRepository<CountryCollection> $countryRepo
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepo
     */
    protected function getMigrationDataConverter(
        EntityWriterInterface $entityWriter,
        MappingService $mappingService,
        MediaFileServiceInterface $mediaFileService,
        EntityRepository $loggingRepo,
        EntityDefinition $dataDefinition,
        EntityRepository $paymentRepo,
        EntityRepository $shippingRepo,
        EntityRepository $countryRepo,
        EntityRepository $salesChannelRepo
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
                    new Shopware55CustomerConverter($mappingService, $loggingService, $validator, $salesChannelRepo),
                    new Shopware55CustomerConverter($mappingService, $loggingService, $validator, $salesChannelRepo),
                    new Shopware55OrderConverter(
                        $mappingService,
                        $loggingService,
                        new TaxCalculator(),
                        $salesChannelRepo
                    ),
                    new Shopware55SalesChannelConverter($mappingService, $loggingService, $paymentRepo, $shippingRepo, $countryRepo, $salesChannelRepo, null),
                    new DummyInvalidCustomerConverter($mappingService, $loggingService, $validator, $salesChannelRepo),
                ]
            )
        );

        return new MigrationDataConverter(
            $entityWriter,
            $converterRegistry,
            $mediaFileService,
            $loggingService,
            $dataDefinition,
            new DummyMappingService()
        );
    }

    /**
     * @param EntityRepository<StateMachineCollection> $stateMachineRepository
     * @param EntityRepository<StateMachineStateCollection> $stateMachineStateRepository
     */
    protected function getOrderStateUuid(
        EntityRepository $stateMachineRepository,
        EntityRepository $stateMachineStateRepository,
        int $oldStateId,
        Context $context
    ): ?string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderDeliveryStates::STATE_MACHINE));

        $stateMachine = $stateMachineRepository->search($criteria, $context)->getEntities()->first();
        static::assertNotNull($stateMachine);

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

        return $stateMachineStateRepository->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<StateMachineCollection> $stateMachineRepository
     * @param EntityRepository<StateMachineStateCollection> $stateMachineStateRepository
     */
    protected function getTransactionStateUuid(
        EntityRepository $stateMachineRepository,
        EntityRepository $stateMachineStateRepository,
        int $oldStateId,
        Context $context
    ): ?string {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_MACHINE));

        $stateMachine = $stateMachineRepository->search($criteria, $context)->getEntities()->first();
        static::assertNotNull($stateMachine);

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

        return $stateMachineStateRepository->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<PaymentMethodCollection> $paymentRepo
     */
    private function getPaymentUuid(EntityRepository $paymentRepo, string $technicalName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $technicalName));
        $criteria->setLimit(1);

        return $paymentRepo->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<SalutationCollection> $salutationRepo
     */
    private function getSalutationUuid(EntityRepository $salutationRepo, string $salutationKey, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', $salutationKey));
        $criteria->setLimit(1);

        return $salutationRepo->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<DeliveryTimeCollection> $deliveryTimeRepo
     */
    private function getFirstDeliveryTimeUuid(EntityRepository $deliveryTimeRepo, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));
        $criteria->setLimit(1);

        return $deliveryTimeRepo->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<CurrencyCollection> $currencyRepo
     */
    private function getCurrencyUuid(EntityRepository $currencyRepo, string $isoCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $isoCode));
        $criteria->setLimit(1);

        return $currencyRepo->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<LocaleCollection> $localeRepo
     * @param EntityRepository<LanguageCollection> $languageRepo
     */
    private function getLanguageUuid(EntityRepository $localeRepo, EntityRepository $languageRepo, string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $code));
        $criteria->setLimit(1);
        $localeId = $localeRepo->searchIds($criteria, $context)->firstId();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('localeId', $localeId));

        return $languageRepo->searchIds($criteria, $context)->firstId();
    }

    /**
     * @param EntityRepository<CategoryCollection> $categoryRepo
     */
    private function getCategoryUuid(EntityRepository $categoryRepo, Context $context): string
    {
        $categoryId = $categoryRepo->searchIds(new Criteria(), $context)->firstId();
        static::assertIsString($categoryId);

        return $categoryId;
    }
}
