<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use SwagMigrationAssistant\DataProvider\Provider\GenerateDocumentProvider;
use SwagMigrationAssistant\DataProvider\Provider\ProviderRegistryInterface;
use SwagMigrationAssistant\DataProvider\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class DataProviderController
{
    /**
     * @var ProviderRegistryInterface
     */
    private $providerRegistry;

    /**
     * @var EnvironmentServiceInterface
     */
    private $environmentService;

    /**
     * @var GenerateDocumentProvider
     */
    private $generateDocumentProvider;

    public function __construct(
        ProviderRegistryInterface $providerRegistry,
        EnvironmentServiceInterface $environmentService,
        GenerateDocumentProvider $generateDocumentProvider
    ) {
        $this->providerRegistry = $providerRegistry;
        $this->environmentService = $environmentService;
        $this->generateDocumentProvider = $generateDocumentProvider;
    }

    /**
     * @Route("/api/v{version}/_action/data-provider/get-environment", name="api.admin.data-provider.get-environment", methods={"GET"})
     * @Acl({"admin"})
     */
    public function getEnvironment(Context $context): Response
    {
        $data = $this->environmentService->getEnvironmentData($context);

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/v{version}/_action/data-provider/get-data", name="api.admin.data-provider.get-data", methods={"GET"})
     * @Acl({"admin"})
     */
    public function getData(Request $request, Context $context): Response
    {
        $identifier = $request->query->get('identifier');
        $limit = (int) $request->query->get('limit', 250);
        $offset = (int) $request->query->get('offset', 0);

        if ($identifier === null) {
            throw new MissingRequestParameterException('identifier');
        }

        $provider = $this->providerRegistry->getDataProvider($identifier);
        $data = $provider->getProvidedData($limit, $offset, $context);

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/v{version}/_action/data-provider/get-total", name="api.admin.data-provider.get-total", methods={"GET"})
     * @Acl({"admin"})
     */
    public function getTotal(Request $request, Context $context): Response
    {
        $identifierArray = $request->query->get('identifierArray', []);

        if (empty($identifierArray)) {
            $providerArray = $this->providerRegistry->getAllDataProviders();
        } else {
            $providerArray = $this->providerRegistry->getDataProviderArray($identifierArray);
        }

        $totals = [];
        foreach ($providerArray as $identifier => $provider) {
            $totals[$identifier] = $provider->getProvidedTotal($context);
        }

        return new JsonResponse($totals);
    }

    /**
     * @Route("/api/v{version}/_action/data-provider/get-table", name="api.admin.data-provider.get-table", methods={"GET"})
     * @Acl({"admin"})
     */
    public function getTable(Request $request, Context $context): Response
    {
        $identifier = $request->query->get('identifier');

        if ($identifier === null) {
            throw new MissingRequestParameterException('identifier');
        }

        $provider = $this->providerRegistry->getDataProvider($identifier);
        $data = $provider->getProvidedTable($context);

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/v{version}/_action/data-provider/generate-document", name="api.admin.data-provider.generate-document", methods={"GET"})
     * @Acl({"admin"})
     */
    public function generateDocument(Request $request, Context $context): JsonResponse
    {
        $identifier = $request->query->get('identifier');

        if ($identifier === null) {
            throw new MissingRequestParameterException('identifier');
        }

        $documentData = $this->generateDocumentProvider->generateDocument($identifier, $context);

        return new JsonResponse($documentData);
    }
}
