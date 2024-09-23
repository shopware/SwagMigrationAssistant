<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\User\UserCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\ShopwareGatewayInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

#[Package('services-settings')]
class UserReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'user';

    /**
     * @var string[]
     */
    private array $destinationUsernameToIdDictionary = [];

    /**
     * @var string[]
     */
    private array $sourceIdToUsernameDictionary = [];

    /**
     * @var array<string, string>
     */
    private array $choiceUuids = [];

    /**
     * @param EntityRepository<UserCollection> $adminUserRepo
     */
    public function __construct(
        protected EntityRepository $adminUserRepo,
        private readonly GatewayRegistryInterface $gatewayRegistry,
    ) {
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext, array $entityGroupNames): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface
            && \in_array(CustomerAndOrderDataSelection::IDENTIFIER, $entityGroupNames, true);
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

        $preMappingData = $gateway->readTable($migrationContext, DefaultEntities::USER);

        $entityData = [];
        foreach ($preMappingData as $data) {
            $this->sourceIdToUsernameDictionary[$data['id']] = $data['username'];

            if (isset($this->connectionPremappingDictionary[$data['id']])) {
                $uuid = $this->connectionPremappingDictionary[$data['id']]->getDestinationUuid();
            }

            if (!isset($uuid) || !isset($this->choiceUuids[$uuid])) {
                $uuid = '';
            }

            $entityData[] = new PremappingEntityStruct($data['id'], $data['username'], $uuid);
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
        $criteria->addSorting(new FieldSorting('username'));
        $adminUsers = $this->adminUserRepo->search($criteria, $context)->getEntities();

        $choices = [];
        foreach ($adminUsers as $adminUser) {
            $id = $adminUser->getId();
            $name = $adminUser->getUsername() ?? '';
            $this->destinationUsernameToIdDictionary[$name] = $id;
            $choices[] = new PremappingChoiceStruct($id, $name);
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
            if (!isset($this->sourceIdToUsernameDictionary[$item->getSourceId()]) || $item->getDestinationUuid() !== '') {
                continue;
            }

            $sourceName = $this->sourceIdToUsernameDictionary[$item->getSourceId()];
            $preselectionValue = $this->destinationUsernameToIdDictionary[$sourceName] ?? null;

            if ($preselectionValue !== null) {
                $item->setDestinationUuid($preselectionValue);
            }
        }
    }
}
