<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Shopware\Core\PlatformRequest;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationFieldExampleGenerator;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use SwagMigrationAssistant\Migration\Validation\MigrationFieldValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [ApiRouteScope::ID]])]
#[Package('fundamentals@after-sales')]
class ErrorResolutionController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly MigrationFieldValidationService $fieldValidationService,
    ) {
    }

    #[Route(
        path: '/api/_action/migration/error-resolution/validate',
        name: 'api.admin.migration.error-resolution.validate',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_POST]
    )]
    public function validateResolution(Request $request, Context $context): JsonResponse
    {
        $entityName = (string) $request->request->get('entityName');
        $fieldName = (string) $request->request->get('fieldName');

        if ($entityName === '') {
            throw MigrationException::missingRequestParameter('entityName');
        }

        if ($fieldName === '') {
            throw MigrationException::missingRequestParameter('fieldName');
        }

        $fieldValue = $this->decodeFieldValue($request->request->all()['fieldValue'] ?? null);

        if ($fieldValue === null) {
            throw MigrationException::missingRequestParameter('fieldValue');
        }

        try {
            $this->fieldValidationService->validateField(
                $entityName,
                $fieldName,
                $fieldValue,
                $context,
            );
        } catch (MigrationValidationException $exception) {
            $previous = $exception->getPrevious();

            if ($previous instanceof WriteConstraintViolationException) {
                return new JsonResponse([
                    'valid' => false,
                    'violations' => $previous->toArray(),
                ]);
            }

            return new JsonResponse([
                'valid' => false,
                'violations' => [['message' => $exception->getMessage()]],
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'valid' => false,
                'violations' => [['message' => $exception->getMessage()]],
            ]);
        }

        return new JsonResponse([
            'valid' => true,
            'violations' => [],
        ]);
    }

    #[Route(
        path: '/api/_action/migration/error-resolution/example-field-structure',
        name: 'api.admin.migration.error-resolution.example-field-structure',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['swag_migration.viewer']],
        methods: [Request::METHOD_POST]
    )]
    public function getExampleFieldStructure(Request $request): JsonResponse
    {
        $entityName = (string) $request->request->get('entityName');
        $fieldName = (string) $request->request->get('fieldName');

        if ($entityName === '') {
            throw MigrationException::missingRequestParameter('entityName');
        }

        if ($fieldName === '') {
            throw MigrationException::missingRequestParameter('fieldName');
        }

        $resolved = $this->fieldValidationService->resolveFieldPath($entityName, $fieldName);

        if ($resolved === null) {
            throw MigrationValidationException::entityFieldNotFound($entityName, $fieldName);
        }

        [, $field] = $resolved;

        $response = [
            'fieldType' => MigrationFieldExampleGenerator::getFieldType($field),
            'example' => MigrationFieldExampleGenerator::generateExample($field),
        ];

        return new JsonResponse($response);
    }

    /**
     * @return array<array-key, mixed>|bool|float|int|string|null
     */
    private function decodeFieldValue(mixed $value): array|bool|float|int|string|null
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (!\is_string($value)) {
            return $value;
        }

        $decoded = \json_decode($value, true);

        return \json_last_error() === \JSON_ERROR_NONE ? $decoded : $value;
    }
}
