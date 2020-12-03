<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

class UnsupportedDocumentTypeLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $technicalName;

    public function __construct(string $runId, string $entity, string $sourceId, string $technicalName)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->technicalName = $technicalName;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_DOCUMENT_TYPE';
    }

    public function getTitle(): string
    {
        return 'Unsupported document type';
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'technicalName' => $this->technicalName,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'Document with source id "%s" could not be converted because of unsupported document type: %s.',
            $args['sourceId'],
            $args['technicalName']
        );
    }
}
