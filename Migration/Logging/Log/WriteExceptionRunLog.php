<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

class WriteExceptionRunLog extends BaseRunLogEntry
{
    /**
     * @var array
     */
    private $error;

    public function __construct(string $runId, string $entity, array $error, ?string $dataId = null)
    {
        parent::__construct($runId, $entity, $dataId);
        $this->error = $error;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__WRITE_EXCEPTION_OCCURRED';
    }

    public function getTitle(): string
    {
        return 'A write exception has occurred';
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'dataId' => $this->getSourceId(),
            'error' => $this->error,
            'description' => \json_encode([
                'entity' => $this->getEntity(),
                'dataId' => $this->getSourceId(),
                'error' => $this->error,
            ], \JSON_PRETTY_PRINT),
        ];
    }

    public function getDescription(): string
    {
        return $this->getParameters()['description'];
    }
}
