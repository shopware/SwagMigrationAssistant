<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Premapping;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingReaderInterface;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\Gateway\TableReaderFactory;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class PaymentMethodReader implements PremappingReaderInterface
{
    private const MAPPING_NAME = 'payment_method';

    /**
     * @var EntityRepositoryInterface
     */
    protected $paymentMethodRepo;

    public function __construct(
        EntityRepositoryInterface $paymentMethodRepo
    ) {
        $this->paymentMethodRepo = $paymentMethodRepo;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME &&
            in_array('customersOrders', $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct
    {
        $mapping = $this->getMapping($migrationContext);
        $choices = $this->getChoices($context);

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
            $entityData[] = new PremappingEntityStruct($data['id'], $data['description'], '');
        }

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $paymentMethods = $this->paymentMethodRepo->search(new Criteria(), $context);

        $choices = [];
        /** @var PaymentMethodEntity $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            $choices[] = new PremappingChoiceStruct($paymentMethod->getId(), $paymentMethod->getName());
        }

        return $choices;
    }
}
