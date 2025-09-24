<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer\MigrationFix;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;

#[Package('after-sales')]
class MigrationFixMapping
{
    /**
     * @var array<MigrationFix>
     */
    public array $migrationFixes = [];

    public bool $hasFix = false;

    public function __construct(
        public readonly string $id,
        public readonly string $connectionId,
        public readonly string $entity,
        public readonly string $oldIdentifier,
        public readonly string $entityUuid,
        public readonly string $entityValue,
        public readonly string $checksum,
        public readonly string $additionalData,
    ) {
    }

    /**
     * @param array<string,string> $data
     */
    public static function fromDatabaseQuery(array $data): self
    {
        $expectedArrayKeys = ['id', 'connection_id', 'entity', 'old_identifier', 'entity_uuid', 'entity_value', 'checksum', 'additional_data'];
        foreach ($expectedArrayKeys as $expectedKey) {
            if (!\array_key_exists($expectedKey, $data)) {
                throw MigrationException::couldNotConvertFixMapping($expectedKey);
            }
        }

        return new self(
            $data['id'],
            $data['connection_id'],
            $data['entity'],
            $data['old_identifier'],
            $data['entity_uuid'],
            $data['entity_value'],
            $data['checksum'],
            $data['additional_data'],
        );
    }

    public function addMigrationFix(MigrationFix $migrationFix): void
    {
        $this->migrationFixes[] = $migrationFix;
        $this->hasFix = true;
    }

    /**
     * @param array<string|int, mixed> $item
     */
    public function applyFixes(array &$item): void
    {
        foreach ($this->migrationFixes as $migrationFix) {
            $migrationFix->apply($item);
        }
    }
}
