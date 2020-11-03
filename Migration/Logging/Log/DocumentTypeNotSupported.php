<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class DocumentTypeNotSupported extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $runId, string $sourceId, string $type)
    {
        parent::__construct($runId, DefaultEntities::ORDER_DOCUMENT, $sourceId);
        $this->type = $type;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__DOCUMENT_TYPE_NOT_SUPPORTED';
    }

    public function getTitle(): string
    {
        return 'Document type is not supported';
    }

    public function getParameters(): array
    {
        return [
            'sourceId' => $this->getSourceId(),
            'type' => $this->type,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The document with the source id "%s" could not be converted because the document type "%s" is not supported.',
            $args['sourceId'],
            $args['type']
        );
    }
}
