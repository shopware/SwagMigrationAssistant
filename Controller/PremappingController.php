<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
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

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    public function __construct(
        PremappingServiceInterface $premappingService,
        EntityRepositoryInterface $migrationRunRepo,
        MigrationContextFactoryInterface $migrationContextFactory
    ) {
        $this->premappingService = $premappingService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->migrationContextFactory = $migrationContextFactory;
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

        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

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

        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new EntityNotExistsException(MigrationContext::class, $runUuid);
        }

        $this->premappingService->writePremapping($context, $migrationContext, $premapping);

        return new JsonResponse();
    }
}
