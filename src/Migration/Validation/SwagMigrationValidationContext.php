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

#[Package('fundamentals@after-sales')]
readonly class SwagMigrationValidationContext
{
    protected SwagMigrationValidationResult $validationResult;

    /**
     * @param array<mixed> $convertedData
     */
    public function __construct(
        protected Context $shopwareContext,
        protected MigrationContextInterface $migrationContext,
        protected EntityDefinition $entityDefinition,
        protected array $convertedData,
    ) {
        $this->validationResult = new SwagMigrationValidationResult(
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
     * @return array<mixed>
     */
    public function getConvertedData(): array
    {
        return $this->convertedData;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entityDefinition;
    }

    public function getValidationResult(): SwagMigrationValidationResult
    {
        return $this->validationResult;
    }
}
