<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

/**
 * @final
 *
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
readonly class MigrationValidationContext
{
    protected MigrationValidationResult $validationResult;

    /**
     * @internal
     *
     * @param array<string, mixed> $convertedData
     * @param array<string, mixed> $sourceData
     */
    public function __construct(
        protected Context $shopwareContext,
        protected MigrationContextInterface $migrationContext,
        protected EntityDefinition $entityDefinition,
        protected array $convertedData,
        protected array $sourceData,
    ) {
        $this->validationResult = new MigrationValidationResult(
            $this->entityDefinition->getEntityName(),
        );
    }

    public function getContext(): Context
    {
        return $this->shopwareContext;
    }

    public function getMigrationContext(): MigrationContextInterface
    {
        return $this->migrationContext;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConvertedData(): array
    {
        return $this->convertedData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSourceData(): array
    {
        return $this->sourceData;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entityDefinition;
    }

    public function getValidationResult(): MigrationValidationResult
    {
        return $this->validationResult;
    }
}
