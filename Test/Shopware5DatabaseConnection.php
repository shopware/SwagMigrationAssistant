<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test;

final class Shopware5DatabaseConnection
{
    public const DB_NAME = 'sw55';
    public const DB_USER = 'root';
    public const DB_PASSWORD = 'app';
    public const DB_HOST = 'mysql';
    public const DB_PORT = '3306';

    private function __construct()
    {
    }
}
