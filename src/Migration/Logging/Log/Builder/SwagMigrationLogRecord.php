<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
readonly class SwagMigrationLogRecord
{
    /**
     * @param array<int, array<string, mixed>>|null $sourceData
     * @param array<int, array<string, mixed>>|null $convertedData
     * @param array<string, mixed>|null $usedMapping
     * @param array<int, array<string, mixed>>|null $exceptionTrace
     */
    public function __construct(
        public string $runId,
        public string $profileName,
        public string $gatewayName,
        public ?string $field = null,
        public ?string $fieldSourcePath = null,
        public ?array $sourceData = null,
        public ?array $convertedData = null,
        public ?array $usedMapping = null,
        public ?string $exceptionMessage = null,
        public ?array $exceptionTrace = null,
    ) {
    }
}
