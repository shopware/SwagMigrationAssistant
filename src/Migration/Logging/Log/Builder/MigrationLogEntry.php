<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
interface MigrationLogEntry
{
    public function getRunId(): string;

    public function getProfileName(): string;

    public function getGatewayName(): string;

    public static function getLevel(): string;

    public static function getCode(): string;

    public static function isUserFixable(): bool;

    public function getEntityId(): ?string;

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

    public function getExceptionMessage(): ?string;

    /**
     * @return array<mixed>|null
     */
    public function getExceptionTrace(): ?array;
}
