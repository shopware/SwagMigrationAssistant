<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DebitPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductReviewDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class PaymentMethodReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'payment_method';

    /**
     * @var EntityRepositoryInterface
     */
    protected $paymentMethodRepo;

    /**
     * @var string[]
     */
    private $preselectionDictionary = [];

    /**
     * @var string[]
     */
    private $preselectionSourceNameDictonary = [];

    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    public function __construct(
        EntityRepositoryInterface $paymentMethodRepo,
        GatewayRegistryInterface $gatewayRegistry
    ) {
        $this->paymentMethodRepo = $paymentMethodRepo;
        $this->gatewayRegistry = $gatewayRegistry;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext, array $entityGroupNames): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && (
                in_array(CustomerAndOrderDataSelection::IDENTIFIER, $entityGroupNames, true)
                || in_array(ProductReviewDataSelection::IDENTIFIER, $entityGroupNames, true)
            );
    }

    public function getPremapping(Context $context, MigrationContextInterface $migrationContext): PremappingStruct
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
    private function getMapping(MigrationContextInterface $migrationContext): array
    {
        /** @var ShopwareGatewayInterface $gateway */
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);

        $preMappingData = $gateway->readTable($migrationContext, 's_core_paymentmeans');

        $entityData = [];
        foreach ($preMappingData as $data) {
            $this->preselectionSourceNameDictonary[$data['id']] = $data['name'];
            $uuid = '';

            if (isset($this->connectionPremappingDictionary[$data['id']])) {
                $uuid = $this->connectionPremappingDictionary[$data['id']]['destinationUuid'];
            }

            $entityData[] = new PremappingEntityStruct($data['id'], $data['description'], $uuid);
        }

        $uuid = '';
        if (isset($this->connectionPremappingDictionary['default_payment_method'])) {
            $uuid = $this->connectionPremappingDictionary['default_payment_method']['destinationUuid'];
        }

        $entityData[] = new PremappingEntityStruct('default_payment_method', 'Standard Payment Method', $uuid);
        usort($entityData, function (PremappingEntityStruct $item1, PremappingEntityStruct $item2) {
            return strcmp($item1->getDescription(), $item2->getDescription());
        });

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));
        $paymentMethods = $this->paymentMethodRepo->search($criteria, $context);

        $choices = [];
        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            $name = $paymentMethod->getName() ?? '';
            $this->preselectionDictionary[$paymentMethod->getHandlerIdentifier()] = $paymentMethod->getId();
            $choices[] = new PremappingChoiceStruct($paymentMethod->getId(), $name);
        }

        return $choices;
    }

    /**
     * @param PremappingEntityStruct[] $mapping
     */
    private function setPreselection(array $mapping): void
    {
        foreach ($mapping as $item) {
            if (!isset($this->preselectionSourceNameDictonary[$item->getSourceId()]) || $item->getDestinationUuid() !== '') {
                continue;
            }

            $sourceName = $this->preselectionSourceNameDictonary[$item->getSourceId()];
            $preselectionValue = $this->getPreselectionValue($sourceName);

            if ($preselectionValue !== null) {
                $item->setDestinationUuid($preselectionValue);
            }
        }
    }

    private function getPreselectionValue(string $sourceName): ?string
    {
        $preselectionValue = null;

        switch ($sourceName) {
            case 'debit':
                $preselectionValue = $this->preselectionDictionary[DebitPayment::class] ?? null;
                break;
            case 'cash':
                $preselectionValue = $this->preselectionDictionary[CashPayment::class] ?? null;
                break;
            case 'invoice':
                $preselectionValue = $this->preselectionDictionary[InvoicePayment::class] ?? null;
                break;
            case 'prepayment':
                $preselectionValue = $this->preselectionDictionary[PrePayment::class] ?? null;
                break;
        }

        return $preselectionValue;
    }
}
