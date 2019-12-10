<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Struct\Struct;

class SwagMigrationAccessTokenStruct extends Struct
{
    /**
     * @var string
     */
    protected $runUuid;

    /**
     * @var string
     */
    protected $accessToken;

    public function __construct(string $runUuid, string $accessToken)
    {
        $this->runUuid = $runUuid;
        $this->accessToken = $accessToken;
    }
}
