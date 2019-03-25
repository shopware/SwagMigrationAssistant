<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\DataSelection\DataSet\DataSet;

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
