<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

#[Package('fundamentals@after-sales')]
interface MigrationContextInterface
{
    public function getProfile(): ProfileInterface;

    public function setProfile(ProfileInterface $profile): void;

    public function getGateway(): GatewayInterface;

    public function setGateway(GatewayInterface $gateway): void;

    public function getConnection(): SwagMigrationConnectionEntity;

    public function setConnection(SwagMigrationConnectionEntity $connection): void;

    public function getRunUuid(): string;

    public function getDataSet(): ?DataSet;

    public function setDataSet(DataSet $dataSet): void;

    public function getOffset(): int;

    public function setOffset(int $offset): void;

    public function getLimit(): int;

    public function setLimit(int $limit): void;
}
