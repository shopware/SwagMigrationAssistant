<?php declare(strict_types=1);

namespace SwagMigrationNext\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationNext\Exception\EntityNotExistsException;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\PremappingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PremappingController extends AbstractController
{
    /**
     * @var PremappingServiceInterface
     */
    private $premappingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    public function __construct(
        PremappingServiceInterface $premappingService,
        EntityRepositoryInterface $migrationRunRepo
    ) {
        $this->premappingService = $premappingService;
        $this->migrationRunRepo = $migrationRunRepo;
    }

    /**
     * @Route("/api/v{version}/_action/migration/generate-premapping", name="api.admin.migration.generate-premapping", methods={"POST"})
     */
    public function generatePremapping(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext(
            $run->getConnection(),
            $runUuid
        );

        return new JsonResponse($this->premappingService->generatePremapping($context, $migrationContext, $run));
    }

    /**
     * @Route("/api/v{version}/_action/migration/write-premapping", name="api.admin.migration.write-premapping", methods={"POST"})
     */
    public function writePremapping(Request $request, Context $context): JsonResponse
    {
        $runUuid = $request->request->get('runUuid');
        $premapping = $request->request->get('premapping');

        if ($runUuid === null) {
            throw new MigrationContextPropertyMissingException('runUuid');
        }

        if ($premapping === null) {
            throw new MigrationContextPropertyMissingException('premapping');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $runUuid);
        }

        $migrationContext = new MigrationContext(
            $run->getConnection(),
            $runUuid
        );

        $this->premappingService->writePremapping($context, $migrationContext, $premapping);

        return new JsonResponse();
    }
}
