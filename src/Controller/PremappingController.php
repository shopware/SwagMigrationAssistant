<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('services-settings')]
class PremappingController extends AbstractController
{
    public function __construct(
        private readonly PremappingServiceInterface $premappingService,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
    ) {
    }

    #[Route(path: '/api/_action/migration/generate-premapping', name: 'api.admin.migration.generate-premapping', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function generatePremapping(Request $request, Context $context): JsonResponse
    {
        $dataSelectionIds = $request->request->all('dataSelectionIds');
        if (empty($dataSelectionIds)) {
            throw RoutingException::missingRequestParameter('dataSelectionIds');
        }

        $migrationContext = $this->migrationContextFactory->createBySelectedConnection($context);

        return new JsonResponse($this->premappingService->generatePremapping($context, $migrationContext, $dataSelectionIds));
    }

    #[Route(path: '/api/_action/migration/write-premapping', name: 'api.admin.migration.write-premapping', methods: ['POST'], defaults: ['_acl' => ['admin']])]
    public function writePremapping(Request $request, Context $context): Response
    {
        $premapping = $request->request->all('premapping');

        if (empty($premapping)) {
            throw RoutingException::missingRequestParameter('premapping');
        }

        $migrationContext = $this->migrationContextFactory->createBySelectedConnection($context);

        $this->premappingService->writePremapping($context, $migrationContext, $premapping);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
