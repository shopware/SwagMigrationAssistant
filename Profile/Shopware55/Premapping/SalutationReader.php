<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\Salutation\SalutationEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Shopware55GatewayInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class SalutationReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'salutation';

    /**
     * @var string[]
     */
    protected $preselectionDictionary = [];

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepo;

    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    public function __construct(
        EntityRepositoryInterface $salutationRepo,
        GatewayRegistryInterface $gatewayRegistry
    ) {
        $this->salutationRepo = $salutationRepo;
        $this->gatewayRegistry = $gatewayRegistry;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME
            && (in_array(CustomerAndOrderDataSelection::IDENTIFIER, $entityGroupNames, true)
            || in_array(NewsletterRecipientDataSelection::IDENTIFIER, $entityGroupNames, true));
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
        /** @var Shopware55GatewayInterface $gateway */
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);

        $result = $gateway->readTable($migrationContext, 's_core_config_elements', ['name' => 'shopsalutations']);
        if (empty($result)) {
            return [];
        }

        $salutations[] = explode(',', unserialize($result[0]['value'], ['allowed_classes' => false]));

        if (empty($salutations)) {
            return [];
        }

        $configuredSalutations = $gateway->readTable($migrationContext, 's_core_config_values', ['element_id' => $result[0]['id']]);

        if (!empty($configuredSalutations)) {
            foreach ($configuredSalutations as $configuredSalutation) {
                $salutations[] = explode(',',
                    unserialize($configuredSalutation['value'], ['allowed_classes' => false]));
            }
        }

        $salutations = array_values(array_unique(array_merge(...$salutations)));
        $entityData = [];

        foreach ($salutations as $salutation) {
            $uuid = '';
            if (isset($this->connectionPremappingDictionary[$salutation])) {
                $uuid = $this->connectionPremappingDictionary[$salutation]['destinationUuid'];
            }

            $entityData[] = new PremappingEntityStruct($salutation, $salutation, $uuid);
        }

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('salutationKey'));
        $salutations = $this->salutationRepo->search($criteria, $context);

        $choices = [];
        /** @var SalutationEntity $salutation */
        foreach ($salutations as $salutation) {
            $this->preselectionDictionary[$salutation->getSalutationKey()] = $salutation->getId();
            $choices[] = new PremappingChoiceStruct($salutation->getId(), $salutation->getSalutationKey());
        }

        return $choices;
    }

    /**
     * @param PremappingEntityStruct[] $mapping
     */
    private function setPreselection(array $mapping): void
    {
        foreach ($mapping as $item) {
            if ($item->getDestinationUuid() !== '' || !isset($this->preselectionDictionary[$item->getSourceId()])) {
                continue;
            }

            $item->setDestinationUuid($this->preselectionDictionary[$item->getSourceId()]);
        }
    }
}
