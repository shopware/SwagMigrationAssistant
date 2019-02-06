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
     * @var int
     */
    protected $categoryTotal;

    /**
     * @var int
     */
    protected $productTotal;

    /**
     * @var int
     */
    protected $customerTotal;

    /**
     * @var int
     */
    protected $orderTotal;

    /**
     * @var int
     */
    protected $mediaTotal;

    /**
     * @var int
     */
    protected $translationTotal;

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

    public function __construct(
        string $sourceSystemName,
        string $sourceSystemVersion,
        string $sourceSystemDomain,
        array $structure = [],
        int $categoryTotal = 0,
        int $productTotal = 0,
        int $customerTotal = 0,
        int $orderTotal = 0,
        int $mediaTotal = 0,
        int $translationTotal = 0,
        int $warningCode = -1,
        string $warningMessage = '',
        int $errorCode = -1,
        string $errorMessage = ''
    ) {
        $this->sourceSystemName = $sourceSystemName;
        $this->sourceSystemVersion = $sourceSystemVersion;
        $this->sourceSystemDomain = $sourceSystemDomain;
        $this->structure = $structure;
        $this->categoryTotal = $categoryTotal;
        $this->productTotal = $productTotal;
        $this->customerTotal = $customerTotal;
        $this->orderTotal = $orderTotal;
        $this->mediaTotal = $mediaTotal;
        $this->translationTotal = $translationTotal;
        $this->warningCode = $warningCode;
        $this->warningMessage = $warningMessage;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
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

    public function getProductTotal(): int
    {
        return $this->productTotal;
    }

    public function getCustomerTotal(): int
    {
        return $this->customerTotal;
    }

    public function getCategoryTotal(): int
    {
        return $this->categoryTotal;
    }

    public function getMediaTotal(): int
    {
        return $this->mediaTotal;
    }

    public function getOrderTotal(): int
    {
        return $this->orderTotal;
    }

    public function getTranslationTotal(): int
    {
        return $this->translationTotal;
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
