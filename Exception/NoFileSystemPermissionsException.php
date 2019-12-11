<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class NoFileSystemPermissionsException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('No file system permissions to create or write to files or directories.');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_MIGRATION__NO_FILE_SYSTEM_PERMISSIONS';
    }
}
