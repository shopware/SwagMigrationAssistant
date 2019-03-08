<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

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
     * @var array
     */
    protected $structure;

    /**
     * @var int[]
     */
    protected $totals;

    /**
     * @var int
     */
    protected $warningCode;

    /**
     * @var string
     */
    protected $warningMessage;

    /**
     * @var int
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
        array $structure = [],
        array $totals = [],
        int $warningCode = -1,
        string $warningMessage = '',
        int $errorCode = -1,
        string $errorMessage = '',
        bool $updateAvailable = false
    ) {
        $this->sourceSystemName = $sourceSystemName;
        $this->sourceSystemVersion = $sourceSystemVersion;
        $this->sourceSystemDomain = $sourceSystemDomain;
        $this->structure = $structure;
        $this->totals = $totals;
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

    public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * @return int[]
     */
    public function getTotals(): array
    {
        return $this->totals;
    }

    public function getWarningCode(): int
    {
        return $this->warningCode;
    }

    public function getWarningMessage(): string
    {
        return $this->warningMessage;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
