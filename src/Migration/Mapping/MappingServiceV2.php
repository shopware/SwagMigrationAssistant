<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;


use Ramsey\Uuid\UuidInterface;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Responsible for mapping
 * (connectionId, entityName, oldIdentifier) -> Uuid
 * (key) -> value
 *
 * The same combination always results in the same Uuid and thus allows
 * migrating data multiple times (and updating it).
 */
#[Package('services-settings')]
class MappingServiceV2 implements ResetInterface
{
    protected UuidInterface $mappingNamespace;

    /** @var array<string, string> */
    protected array $mappingCache = [];
    /** @var array<string, string> */
    protected array $valueCache = [];

    /**
     * @param string $connectionId
     * @param array<string, string> $mappings (entityName . oldIdentifier) -> uuid
     * @param array<string, string> $values (key) -> value
     * @internal
     */
    public function __construct(string $connectionId, array $mappings, array $values)
    {
        $this->mappingNamespace = \Ramsey\Uuid\Uuid::fromString($connectionId);
        $this->mappingCache = $mappings;
        $this->valueCache = $values;
    }

    /**
     * Maps (connectionId, entityName, oldIdentifier) -> Uuid.
     * connectionId is already given in the constructor of this class.
     *
     * The same combination always results in the same Uuid and thus allows
     * migrating data multiple times (and updating it).
     *
     * @param string $entityName
     * @param string $oldIdentifier
     * @return string Uuid
     */
    public function map(string $entityName, string $oldIdentifier): string
    {
        if (isset($this->mappingCache[$entityName . $oldIdentifier])) {
            return $this->mappingCache[$entityName . $oldIdentifier];
        }

        $uuid = \Ramsey\Uuid\Uuid::uuid3($this->mappingNamespace, $entityName . $oldIdentifier);
        return bin2hex($uuid->getBytes());
    }

    /**
     * Maps (key) -> value.
     *
     * Can be used to lookup user specified values (specified in the premapping)
     * e.g. the fallback newsletter recipient status that should be used during migration.
     *
     * @param string $key
     * @return string value that was looked up
     * @throws \Exception
     */
    public function mapValue(string $key): string
    {
        if (isset($this->valueCache[$key])) {
            return $this->mappingCache[$key];
        }

        throw new \Exception("ToDo: mapValue exception");
    }

    // Todo: check if needed
    public function setMappingCache(array $mappings): void
    {
        $this->mappingCache = $mappings;
    }

    // Todo: check if needed
    public function reset(): void
    {
        $this->mappingCache = [];
    }
}
