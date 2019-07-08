<?php declare(strict_types=1);

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
    protected $updateAvailable;

    /**
     * @var string
     */
    protected $targetSystemCurrency;

    /**
     * @var string
     */
    protected $sourceSystemCurrency;

    /**
     * @param TotalStruct[] $totals
     */
    public function __construct(
        string $sourceSystemName,
        string $sourceSystemVersion,
        string $sourceSystemDomain,
        array $totals = [],
        array $additionalData = [],
        ?RequestStatusStruct $requestStatus = null,
        bool $updateAvailable = false,
        string $targetSystemCurrency = '',
        string $sourceSystemCurrency = ''
    ) {
        $this->sourceSystemName = $sourceSystemName;
        $this->sourceSystemVersion = $sourceSystemVersion;
        $this->sourceSystemDomain = $sourceSystemDomain;
        $this->totals = $totals;
        $this->additionalData = $additionalData;
        $this->requestStatus = $requestStatus;
        $this->updateAvailable = $updateAvailable;
        $this->targetSystemCurrency = $targetSystemCurrency;
        $this->sourceSystemCurrency = $sourceSystemCurrency;
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

    public function getTargetSystemCurrency(): string
    {
        return $this->targetSystemCurrency;
    }

    public function getSourceSystemCurrency(): string
    {
        return $this->sourceSystemCurrency;
    }
}
