<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

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
