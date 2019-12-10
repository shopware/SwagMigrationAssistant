<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Struct\Struct;

class EnvironmentInformation extends Struct
{
    /**
     * @var string
     */
    protected $sourceSystemName;

    /**
     * @var string
     */
    protected $sourceSystemVersion;

    /**
     * @var string
     */
    protected $sourceSystemDomain;

    /**
     * @var TotalStruct[]
     */
    protected $totals;

    /**
     * @var array
     */
    protected $additionalData;

    /**
     * @var RequestStatusStruct|null
     */
    protected $requestStatus;

    /**
     * @var bool
     */
    protected $migrationDisabled;

    /**
     * @var string
     */
    protected $targetSystemCurrency;

    /**
     * @var string
     */
    protected $sourceSystemCurrency;

    /**
     * @var DisplayWarning[]
     */
    protected $displayWarnings;

    /**
     * @param TotalStruct[]    $totals
     * @param DisplayWarning[] $displayWarnings
     */
    public function __construct(
        string $sourceSystemName,
        string $sourceSystemVersion,
        string $sourceSystemDomain,
        array $totals = [],
        array $additionalData = [],
        ?RequestStatusStruct $requestStatus = null,
        bool $migrationDisabled = false,
        array $displayWarnings = [],
        string $targetSystemCurrency = '',
        string $sourceSystemCurrency = ''
    ) {
        $this->sourceSystemName = $sourceSystemName;
        $this->sourceSystemVersion = $sourceSystemVersion;
        $this->sourceSystemDomain = $sourceSystemDomain;
        $this->totals = $totals;
        $this->additionalData = $additionalData;
        $this->requestStatus = $requestStatus;
        $this->migrationDisabled = $migrationDisabled;
        $this->targetSystemCurrency = $targetSystemCurrency;
        $this->sourceSystemCurrency = $sourceSystemCurrency;
        $this->displayWarnings = $displayWarnings;
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
     * @return TotalStruct[]
     */
    public function getTotals(): array
    {
        return $this->totals;
    }

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
}
