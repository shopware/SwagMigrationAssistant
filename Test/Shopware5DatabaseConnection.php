<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
