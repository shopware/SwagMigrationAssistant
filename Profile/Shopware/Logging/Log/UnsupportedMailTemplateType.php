<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

class UnsupportedMailTemplateType extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $runId, string $sourceId, string $type)
    {
        parent::__construct($runId, null, $sourceId);
        $this->type = $type;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_MAIL_TEMPLATE_TYPE';
    }

    public function getTitle(): string
    {
        return 'Unsupported mail type';
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
            'MailTemplate-Entity with source id "%s" could not be converted because of unsupported type: %s.',
            $args['sourceId'],
            $args['type']
        );
    }
}
