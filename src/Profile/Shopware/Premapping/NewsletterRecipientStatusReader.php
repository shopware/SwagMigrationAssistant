<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class NewsletterRecipientStatusReader extends AbstractPremappingReader
{
    public const SOURCE_ID = 'default_newsletter_recipient_status';
    private const MAPPING_NAME = 'newsletter_status';

    private string $connectionPremappingValue = '';

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(MigrationContextInterface $migrationContext, array $entityGroupNames): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && \in_array(NewsletterRecipientDataSelection::IDENTIFIER, $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContextInterface $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingValue($migrationContext);
        $mapping = $this->getMapping();
        $choices = $this->getChoices();

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    protected function fillConnectionPremappingValue(MigrationContextInterface $migrationContext): void
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return;
        }

        $mappingArray = $connection->getPremapping();

        if ($mappingArray === null) {
            return;
        }

        foreach ($mappingArray as $premapping) {
            if ($premapping->getEntity() !== self::MAPPING_NAME) {
                continue;
            }

            foreach ($premapping->getMapping() as $mapping) {
                $this->connectionPremappingValue = $mapping->getDestinationUuid();
            }
        }
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(): array
    {
        $entityData = [];
        $entityData[] = new PremappingEntityStruct(self::SOURCE_ID, 'Standard newsletter status', $this->connectionPremappingValue);

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(): array
    {
        $choices = [];
        $choices[] = new PremappingChoiceStruct('direct', 'Direct');
        $choices[] = new PremappingChoiceStruct('notSet', 'Not set');
        $choices[] = new PremappingChoiceStruct('optIn', 'OptIn');
        $choices[] = new PremappingChoiceStruct('optOut', 'OptOut');

        return $choices;
    }
}
