<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration;

use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

interface MigrationContextInterface
{
    public function getRunUuid(): string;

    public function getConnection(): ?SwagMigrationConnectionEntity;

    public function getDataSet(): ?DataSet;

    public function getOffset(): int;

    public function getLimit(): int;

    public function getProfile(): ProfileInterface;

    public function setProfile(ProfileInterface $profile): void;

    public function getGateway(): GatewayInterface;

    public function setGateway(GatewayInterface $gateway): void;
}
