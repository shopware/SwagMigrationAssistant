<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Salutation\SalutationCollection;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductReviewDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\PromotionDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\WishlistDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class SalutationReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'salutation';

    /**
     * @var array<string>
     */
    protected array $preselectionDictionary = [];

    /**
     * @var array<string, string>
     */
    private array $choiceUuids = [];

    /**
     * @param EntityRepository<SalutationCollection> $salutationRepo
     */
    public function __construct(
        private readonly EntityRepository $salutationRepo,
        private readonly GatewayRegistryInterface $gatewayRegistry
    ) {
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext, array $entityGroupNames): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && (\in_array(CustomerAndOrderDataSelection::IDENTIFIER, $entityGroupNames, true)
            || \in_array(NewsletterRecipientDataSelection::IDENTIFIER, $entityGroupNames, true)
            || \in_array(ProductReviewDataSelection::IDENTIFIER, $entityGroupNames, true)
            || \in_array(WishlistDataSelection::IDENTIFIER, $entityGroupNames, true)
            || \in_array(PromotionDataSelection::IDENTIFIER, $entityGroupNames, true));
    }

    public function getPremapping(Context $context, MigrationContextInterface $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingDictionary($migrationContext);
        $choices = $this->getChoices($context);
        $mapping = $this->getMapping($migrationContext);
        $this->setPreselection($mapping);

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(MigrationContextInterface $migrationContext): array
    {
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);

        if (!$gateway instanceof ShopwareGatewayInterface) {
            return [];
        }

        $result = $gateway->readTable($migrationContext, 's_core_config_elements', ['name' => 'shopsalutations']);
        if (empty($result)) {
            return [];
        }

        $salutations = [];
        $salutations[] = \explode(',', \unserialize($result[0]['value'], ['allowed_classes' => false]));
        $salutations = \array_filter($salutations);

        $configuredSalutations = $gateway->readTable($migrationContext, 's_core_config_values', ['element_id' => $result[0]['id']]);

        if (!empty($configuredSalutations)) {
            foreach ($configuredSalutations as $configuredSalutation) {
                $salutations[] = \explode(
                    ',',
                    \unserialize($configuredSalutation['value'], ['allowed_classes' => false])
                );
            }
        }

        $salutations = \array_values(\array_unique(\array_merge(...$salutations)));
        $entityData = [];

        foreach ($salutations as $salutation) {
            $uuid = '';
            if (isset($this->connectionPremappingDictionary[$salutation])) {
                $uuid = $this->connectionPremappingDictionary[$salutation]->getDestinationUuid();

                if (!isset($this->choiceUuids[$uuid])) {
                    $uuid = '';
                }
            }

            $entityData[] = new PremappingEntityStruct($salutation, $salutation, $uuid);
        }
        \usort($entityData, function (PremappingEntityStruct $item1, PremappingEntityStruct $item2) {
            return \strcmp($item1->getDescription(), $item2->getDescription());
        });

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('salutationKey'));
        $salutations = $this->salutationRepo->search($criteria, $context)->getEntities();

        $choices = [];
        foreach ($salutations as $salutation) {
            $key = $salutation->getSalutationKey() ?? '';

            $id = $salutation->getId();
            $this->preselectionDictionary[$key] = $id;
            $choices[] = new PremappingChoiceStruct($id, $key);
            $this->choiceUuids[$id] = $id;
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
