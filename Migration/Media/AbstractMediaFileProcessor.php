<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Media;

abstract class AbstractMediaFileProcessor implements MediaFileProcessorInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $this->getSupportedProfileName() === $profileName
            && $this->getSupportedGatewayIdentifier() === $gatewayIdentifier;
    }
}
