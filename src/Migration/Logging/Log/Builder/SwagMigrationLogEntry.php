<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
interface SwagMigrationLogEntry
{
    public function getRunId(): string;

    public function getProfileName(): string;

    public function getGatewayName(): string;

    public function getLevel(): string;

    public function getCode(): string;

    public function isUserFixable(): bool;

    public function getEntityName(): ?string;

    public function getFieldName(): ?string;

    public function getFieldSourcePath(): ?string;

    /**
     * @return array<mixed>|null
     */
    public function getSourceData(): ?array;

    /**
     * @return array<mixed>|null
     */
    public function getConvertedData(): ?array;

    /**
     * @return array<mixed>|null
     */
    public function getUsedMapping(): ?array;

    public function getExceptionMessage(): ?string;

    /**
     * @return array<mixed>|null
     */
    public function getExceptionTrace(): ?array;
}
