<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class DocumentTypeNotSupported extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $sourceId,
        private readonly string $type
    ) {
        parent::__construct($runId, DefaultEntities::ORDER_DOCUMENT, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__DOCUMENT_TYPE_NOT_SUPPORTED';
    }

    public function getTitle(): string
    {
        return 'Document type is not supported';
    }

    /**
     * @return array{sourceId: ?string, type: string}
     */
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
            'The document with the source id "%s" has the document type "%s", which just got migrated but is still missing a document renderer. If you want to generate documents of this type, please follow this documentation: https://developer.shopware.com/docs/guides/plugins/plugins/checkout/document/add-custom-document-type.html',
            $args['sourceId'],
            $args['type']
        );
    }
}
