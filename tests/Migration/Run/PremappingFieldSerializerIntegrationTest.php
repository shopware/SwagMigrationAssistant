<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Run;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Run\PremappingField;
use SwagMigrationAssistant\Migration\Run\PremappingFieldSerializer;

class PremappingFieldSerializerIntegrationTest extends TestCase
{
    use KernelTestBehaviour;

    private PremappingFieldSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = $this->getContainer()->get(PremappingFieldSerializer::class);
    }

    public static function invalidInputProvider(): \Generator
    {
        yield 'Unset entity key of main data' => ['entity'];
        yield 'Unset mapping key of main data' => ['mapping'];
        yield 'Unset choices key of main data' => ['choices'];

        yield 'Unset sourceId key of mapping' => ['sourceId', 'mapping'];
        yield 'Unset description key of mapping' => ['description', 'mapping'];
        yield 'Unset destinationUuid key of mapping' => ['destinationUuid', 'mapping'];

        yield 'Unset uuid key of choices' => ['uuid', 'choices'];
        yield 'Unset description key of choices' => ['description', 'choices'];
    }

    #[DataProvider('invalidInputProvider')]
    public function testEncodeWithInvalidInput(string $missingKey, string $mainKey = ''): void
    {
        $input = [[
            'entity' => 'entity',
            'mapping' => [
                [
                    'sourceId' => 'sourceId',
                    'description' => 'Mapping #1',
                    'destinationUuid' => 'newId',
                ],
            ],
            'choices' => [
                [
                    'uuid' => 'newId',
                    'description' => 'New ID',
                ],
            ],
        ]];

        if ($mainKey === '') {
            unset($input[0][$missingKey]);
            $expectedPropertyPath = "/someId/0/{$missingKey}";
        } else {
            unset($input[0][$mainKey][0][$missingKey]);
            $expectedPropertyPath = "/someId/0/{$mainKey}/0/{$missingKey}";
        }

        try {
            $this->serializer->encode(
                new PremappingField('premapping', 'premapping'),
                new EntityExistence('premapping', ['someId' => 'foo'], true, false, false, []),
                new KeyValuePair('someId', $input, false),
                new WriteParameterBag(
                    new SwagMigrationConnectionDefinition(),
                    WriteContext::createFromContext(Context::createDefaultContext()),
                    '',
                    new WriteCommandQueue()
                )
            )->current();
        } catch (WriteConstraintViolationException $e) {
            $violation = $e->getViolations()->getIterator()->current();

            static::assertSame($expectedPropertyPath, $violation->getPropertyPath());

            return;
        }

        static::fail('No exception was thrown');
    }
}
