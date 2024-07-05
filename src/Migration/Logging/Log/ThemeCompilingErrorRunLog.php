<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class ThemeCompilingErrorRunLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $sourceId
    ) {
        parent::__construct($runId, null, $sourceId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__THEME_COMPILING_ERROR';
    }

    public function getTitle(): string
    {
        return 'Theme compiling error';
    }

    /**
     * @return array{sourceId: ?string}
     */
    public function getParameters(): array
    {
        return [
            'sourceId' => $this->getSourceId(),
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            'The theme with id "%s" could not be compiled.',
            $args['sourceId']
        );
    }
}
