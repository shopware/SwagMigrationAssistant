<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Premapping;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationNext\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\Gateway\TableReaderFactory;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

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

    public function __construct(EntityRepositoryInterface $paymentMethodRepo)
    {
        $this->paymentMethodRepo = $paymentMethodRepo;
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

        $preMappingData = $reader->read('s_core_paymentmeans');

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
            $this->preselectionDictionary[$paymentMethod->getHandlerIdentifier()] = $paymentMethod->getId();
            $choices[] = new PremappingChoiceStruct($paymentMethod->getId(), $paymentMethod->getName());
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

            if (!isset($this->preselectionDictionary[$sourceName])) {
                continue;
            }

            $item->setDestinationUuid($this->preselectionDictionary[$sourceName]);
        }
    }
}
