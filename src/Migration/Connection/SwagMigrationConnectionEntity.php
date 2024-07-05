<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;

#[Package('services-settings')]
class SwagMigrationConnectionEntity extends Entity
{
    use EntityIdTrait;

    protected string $name = '';

    /**
     * @var array<string, int|string>|null
     */
    protected ?array $credentialFields = null;

    /**
     * @var array<PremappingStruct>
     */
    protected array $premapping = [];

    protected string $profileName = '';

    protected string $gatewayName = '';

    protected ?SwagMigrationRunCollection $runs = null;

    protected ?SwagMigrationMappingCollection $mappings = null;

    protected ?GeneralSettingCollection $settings = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string, int|string>|null
     */
    public function getCredentialFields(): ?array
    {
        return $this->credentialFields;
    }

    /**
     * @param array<string, int|string> $credentialFields
     */
    public function setCredentialFields(array $credentialFields): void
    {
        $this->credentialFields = $credentialFields;
    }

    /**
     * @return array<PremappingStruct>|null
     */
    public function getPremapping(): ?array
    {
        return $this->premapping;
    }

    /**
     * @param array<PremappingStruct> $premapping
     */
    public function setPremapping(array $premapping): void
    {
        $this->premapping = $premapping;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function setProfileName(string $profileName): void
    {
        $this->profileName = $profileName;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function setGatewayName(string $gatewayName): void
    {
        $this->gatewayName = $gatewayName;
    }

    public function getRuns(): ?SwagMigrationRunCollection
    {
        return $this->runs;
    }

    public function setRuns(SwagMigrationRunCollection $runs): void
    {
        $this->runs = $runs;
    }

    public function getMappings(): ?SwagMigrationMappingCollection
    {
        return $this->mappings;
    }

    public function setMappings(SwagMigrationMappingCollection $mappings): void
    {
        $this->mappings = $mappings;
    }

    public function getSettings(): ?GeneralSettingCollection
    {
        return $this->settings;
    }

    public function setSettings(GeneralSettingCollection $settings): void
    {
        $this->settings = $settings;
    }
}
