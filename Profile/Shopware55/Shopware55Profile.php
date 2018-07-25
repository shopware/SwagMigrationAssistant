<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
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

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    public function __construct(
        RepositoryInterface $migrationDataRepo,
        ConverterRegistryInterface $converterRegistry,
        MappingServiceInterface $mappingService
    ) {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->converterRegistry = $converterRegistry;
        $this->mappingService = $mappingService;
    }

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function collectData(GatewayInterface $gateway, string $entityName, Context $context): void
    {
        /** @var array[] $data */
        $data = $gateway->read($entityName);

        $this->mappingService->setProfile($this->getName());
        $this->mappingService->readExistingMappings($context);

        $converter = $this->converterRegistry->getConverter($entityName);
        $createData = [];
        foreach ($data as $item) {
            $convertStruct = $converter->convert($item);
            $createData[] = [
                'entity' => $entityName,
                'profile' => $this->getName(),
                'raw' => $item,
                'converted' => $convertStruct->getConverted(),
                'unmapped' => $convertStruct->getUnmapped(),
            ];
        }

        $this->mappingService->writeMapping($context);
        $this->migrationDataRepo->upsert($createData, $context);
    }
}
