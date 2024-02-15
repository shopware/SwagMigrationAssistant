<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

#[Package('services-settings')]
class DeactivatedPackLanguageLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $entity,
        string $sourceId,
        private readonly string $languageId
    ) {
        parent::__construct($runId, $entity, $sourceId);
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

    /**
     * @return array{entity: ?string, sourceId: ?string, packLanguage: string}
     */
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
