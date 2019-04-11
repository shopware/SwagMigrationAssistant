<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationNext\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class SalesChannelReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'salesChannel';

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var string
     */
    private $connectionPremappingValue = '';

    public function __construct(EntityRepositoryInterface $salesChannelRepository)
    {
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME
            && in_array('newsletterReceiver', $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingValue($migrationContext);
        $mapping = $this->getMapping();
        $choices = $this->getChoices($context);

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    protected function fillConnectionPremappingValue(MigrationContext $migrationContext): void
    {
        if ($migrationContext->getConnection()->getPremapping() === null) {
            return;
        }

        foreach ($migrationContext->getConnection()->getPremapping() as $premapping) {
            if ($premapping['entity'] === self::MAPPING_NAME) {
                foreach ($premapping['mapping'] as $mapping) {
                    $this->connectionPremappingValue = $mapping['destinationUuid'];
                }
            }
        }
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(): array
    {
        $entityData[] = new PremappingEntityStruct('default_salesChannel', 'Default Sales Channel', $this->connectionPremappingValue);

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));
        $salesChannels = $this->salesChannelRepository->search($criteria, $context);

        $choices = [];
        /* @var SalesChannelEntity $manufacturer */
        foreach ($salesChannels as $salesChannel) {
            $choices[] = new PremappingChoiceStruct($salesChannel->getId(), $salesChannel->getName());
        }

        return $choices;
    }
}
