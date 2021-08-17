<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

class DeactivatedPackLanguageLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $languageId;

    public function __construct(string $runId, string $entity, string $sourceId, string $languageId)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->languageId = $languageId;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__DEACTIVATED_PACK_LANGUAGE';
    }

    public function getTitle(): string
    {
        return 'Deactivated pack language';
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'packLanguage' => $this->languageId,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'Language for %s with source id "%s" has been set to default language. The language with id "%s" is not activated for sales channels by "Language pack" plugin.',
            $args['entity'],
            $args['sourceId'],
            $args['packLanguage']
        );
    }
}
