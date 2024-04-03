<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class EnvironmentInformation extends Struct
{
    /**
     * @param array<string, TotalStruct> $totals
     * @param array<string, mixed> $additionalData
     * @param DisplayWarning[] $displayWarnings
     */
    public function __construct(
        protected string $sourceSystemName,
        protected string $sourceSystemVersion,
        protected string $sourceSystemDomain,
        protected array $totals = [],
        protected array $additionalData = [],
        protected ?RequestStatusStruct $requestStatus = null,
        protected bool $migrationDisabled = false,
        protected array $displayWarnings = [],
        protected string $targetSystemCurrency = '',
        protected string $sourceSystemCurrency = '',
        protected string $sourceSystemLocale = '',
        protected string $targetSystemLocale = ''
    ) {
    }

    public function getSourceSystemName(): string
    {
        return $this->sourceSystemName;
    }

    public function getSourceSystemVersion(): string
    {
        return $this->sourceSystemVersion;
    }

    public function getSourceSystemDomain(): string
    {
        return $this->sourceSystemDomain;
    }

    /**
     * @return array<string, TotalStruct>
     */
    public function getTotals(): array
    {
        return $this->totals;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function getRequestStatus(): ?RequestStatusStruct
    {
        return $this->requestStatus;
    }

    public function isMigrationDisabled(): bool
    {
        return $this->migrationDisabled;
    }

    /**
     * @return DisplayWarning[]
     */
    public function getDisplayWarnings(): array
    {
        return $this->displayWarnings;
    }

    public function getTargetSystemCurrency(): string
    {
        return $this->targetSystemCurrency;
    }

    public function getSourceSystemCurrency(): string
    {
        return $this->sourceSystemCurrency;
    }

    public function getSourceSystemLocale(): string
    {
        return $this->sourceSystemLocale;
    }

    public function setSourceSystemLocale(string $sourceSystemLocale): void
    {
        $this->sourceSystemLocale = $sourceSystemLocale;
    }

    public function getTargetSystemLocale(): string
    {
        return $this->targetSystemLocale;
    }

    public function setTargetSystemLocale(string $targetSystemLocale): void
    {
        $this->targetSystemLocale = $targetSystemLocale;
    }
}
