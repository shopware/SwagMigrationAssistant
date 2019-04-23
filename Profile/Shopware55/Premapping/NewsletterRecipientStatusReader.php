<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Premapping;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class NewsletterRecipientStatusReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'newsletter_status';

    /**
     * @var string
     */
    private $connectionPremappingValue = '';

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME
            && in_array(NewsletterRecipientDataSelection::IDENTIFIER, $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingValue($migrationContext);
        $mapping = $this->getMapping();
        $choices = $this->getChoices();

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
        $entityData[] = new PremappingEntityStruct('default_newsletter_recipient_status', 'Standard newsletter status', $this->connectionPremappingValue);

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(): array
    {
        $choices[] = new PremappingChoiceStruct('notSet', 'Not set');
        $choices[] = new PremappingChoiceStruct('optIn', 'OptIn');
        $choices[] = new PremappingChoiceStruct('optOut', 'OptOut');
        $choices[] = new PremappingChoiceStruct('direct', 'Direct');

        return $choices;
    }
}