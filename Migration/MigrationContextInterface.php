<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration;

use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;

interface MigrationContextInterface
{
    public function getRunUuid(): string;

    public function getConnection(): ?SwagMigrationConnectionEntity;

    public function getProfileName(): ?string;

    public function getGatewayName(): ?string;

    public function getDataSet(): ?DataSet;

    public function getOffset(): int;

    public function getLimit(): int;
}
