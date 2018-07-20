<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Profile\ProfileInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterRegistryInterface;

class Shopware55Profile implements ProfileInterface
{
    public const PROFILE_NAME = 'shopware55';

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    public function __construct(RepositoryInterface $migrationDataRepo, ConverterRegistryInterface $converterRegistry)
    {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->converterRegistry = $converterRegistry;
    }

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function collectData(GatewayInterface $gateway, string $entityName, Context $context, array $additionalRelationData = []): void
    {
        $converter = $this->converterRegistry->getConverter($entityName);
        /** @var array[] $data */
        $data = $gateway->read($entityName);

        $createData = [];
        foreach ($data as $id => $item) {
            $convertStruct = $converter->convert($item, $additionalRelationData);
            $createData[] = [
                'entityName' => $entityName,
                'profile' => $this->getName(),
                'raw' => $item,
                'converted' => $convertStruct->getConverted(),
                'unmapped' => $convertStruct->getUnmapped(),
                'oldIdentifier' => (string) $id,
                'entityUuid' => $convertStruct->getUuid(),
            ];
        }

        $this->migrationDataRepo->upsert($createData, $context);
    }
}
