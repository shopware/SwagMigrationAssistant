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
     * @var int[]
     */
    protected $totals;

    /**
     * @var array
     */
    protected $additionalData;

    /**
     * @var string
     */
    protected $warningCode;

    /**
     * @var string
     */
    protected $warningMessage;

    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var bool
     */
    protected $updateAvailable;

    /**
     * @param int[] $totals
     */
    public function __construct(
        string $sourceSystemName,
        string $sourceSystemVersion,
        string $sourceSystemDomain,
        array $totals = [],
        array $additionalData = [],
        string $warningCode = '',
        string $warningMessage = '',
        string $errorCode = '',
        string $errorMessage = '',
        bool $updateAvailable = false
    ) {
        $this->sourceSystemName = $sourceSystemName;
        $this->sourceSystemVersion = $sourceSystemVersion;
        $this->sourceSystemDomain = $sourceSystemDomain;
        $this->totals = $totals;
        $this->additionalData = $additionalData;
        $this->warningCode = $warningCode;
        $this->warningMessage = $warningMessage;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->updateAvailable = $updateAvailable;
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
     * @return int[]
     */
    public function getTotals(): array
    {
        return $this->totals;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function getWarningCode(): string
    {
        return $this->warningCode;
    }

    public function getWarningMessage(): string
    {
        return $this->warningMessage;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
