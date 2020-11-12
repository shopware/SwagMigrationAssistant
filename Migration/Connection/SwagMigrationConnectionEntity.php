<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;

class SwagMigrationConnectionEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array|null
     */
    protected $credentialFields;

    /**
     * @var array
     */
    protected $premapping;

    /**
     * @var string
     */
    protected $profileName;

    /**
     * @var string
     */
    protected $gatewayName;

    /**
     * @var SwagMigrationRunCollection|null
     */
    protected $runs;

    /**
     * @var SwagMigrationMappingCollection|null
     */
    protected $mappings;

    /**
     * @var GeneralSettingCollection|null
     */
    protected $settings;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCredentialFields(): ?array
    {
        return $this->credentialFields;
    }

    public function setCredentialFields(array $credentialFields): void
    {
        $this->credentialFields = $credentialFields;
    }

    public function getPremapping(): ?array
    {
        return $this->premapping;
    }

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
