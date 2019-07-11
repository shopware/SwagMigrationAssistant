<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration;

use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

interface MigrationContextInterface
{
    public function getProfile(): ProfileInterface;

    public function getConnection(): ?SwagMigrationConnectionEntity;

    public function getRunUuid(): string;

    public function getDataSet(): ?DataSet;

    public function getOffset(): int;

    public function getLimit(): int;

    public function getGateway(): GatewayInterface;

    public function setGateway(GatewayInterface $gateway): void;
}
