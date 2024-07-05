<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Run;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationProgressField;
use SwagMigrationAssistant\Migration\Run\MigrationProgressFieldSerializer;
use SwagMigrationAssistant\Migration\Run\ProgressDataSet;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;

class MigrationProgressFieldSerializerTest extends TestCase
{
    use KernelTestBehaviour;

    private MigrationProgressFieldSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = $this->getContainer()->get(MigrationProgressFieldSerializer::class);
    }

    public function testEncodeWithArrayInput(): void
    {
        $expected = [
            'progress' => 1,
            'total' => 2,
            'currentEntity' => 'currentEntity',
            'currentEntityProgress' => 3,
            'dataSets' => [
                [
                    'entityName' => ProductDefinition::ENTITY_NAME,
                    'total' => 10,
                ],
            ],
            'exceptionCount' => 0,
            'isAborted' => 0,
        ];

        $input = $expected;
        $input['extensions'] = [];
        $input['dataSets'][0]['extensions'] = [];
        $input['isAborted'] = (bool) $expected['isAborted'];

        $encoded = $this->serializer->encode(
            new MigrationProgressField('progress', 'progress'),
            new EntityExistence('progress', ['someId' => 'foo'], true, false, false, []),
            new KeyValuePair('someId', $input, false),
            new WriteParameterBag(new SwagMigrationRunDefinition(), WriteContext::createFromContext(Context::createDefaultContext()), '', new WriteCommandQueue())
        )->current();

        static::assertSame(\json_encode($expected), $encoded);
    }

    public function testEncodeWithStructInput(): void
    {
        $expected = [
            'progress' => 1,
            'total' => 2,
            'currentEntity' => 'currentEntity',
            'currentEntityProgress' => 3,
            'dataSets' => [
                [
                    'entityName' => ProductDefinition::ENTITY_NAME,
                    'total' => 10,
                ],
            ],
            'exceptionCount' => 0,
            'isAborted' => 1,
        ];

        $input = new MigrationProgress(
            $expected['progress'],
            $expected['total'],
            new ProgressDataSetCollection([new ProgressDataSet(ProductDefinition::ENTITY_NAME, 10)]),
            $expected['currentEntity'],
            $expected['currentEntityProgress'],
            $expected['exceptionCount'],
            (bool) $expected['isAborted'],
        );

        $encoded = $this->serializer->encode(
            new MigrationProgressField('progress', 'progress'),
            new EntityExistence('progress', ['someId' => 'foo'], true, false, false, []),
            new KeyValuePair('someId', $input, false),
            new WriteParameterBag(new SwagMigrationRunDefinition(), WriteContext::createFromContext(Context::createDefaultContext()), '', new WriteCommandQueue())
        )->current();

        static::assertSame(\json_encode($expected), $encoded);
    }

    public function testDecode(): void
    {
        $expected = new MigrationProgress(
            1,
            2,
            new ProgressDataSetCollection([ProductDefinition::ENTITY_NAME => new ProgressDataSet(ProductDefinition::ENTITY_NAME, 10)]),
            'currentEntity',
            3
        );

        $input = [
            'progress' => 1,
            'total' => 2,
            'currentEntity' => 'currentEntity',
            'currentEntityProgress' => 3,
            'dataSets' => [
                [
                    'entityName' => ProductDefinition::ENTITY_NAME,
                    'total' => 10,
                ],
            ],
            'exceptionCount' => 0,
            'isAborted' => false,
        ];

        $decoded = $this->serializer->decode(
            new MigrationProgressField('progress', 'progress'),
            \json_encode($input)
        );

        static::assertEquals($expected, $decoded);
    }

    public function testDecodeWithoutDataSetKey(): void
    {
        $expected = new MigrationProgress(
            1,
            2,
            new ProgressDataSetCollection(),
            'currentEntity',
            3
        );

        $input = [
            'progress' => 1,
            'total' => 2,
            'currentEntity' => 'currentEntity',
            'currentEntityProgress' => 3,
            'exceptionCount' => 0,
            'isAborted' => false,
        ];

        $decoded = $this->serializer->decode(
            new MigrationProgressField('progress', 'progress'),
            \json_encode($input)
        );

        static::assertEquals($expected, $decoded);
    }

    #[DataProvider('invalidInputProvider')]
    public function testEncodeWithInvalidInput(string $missingKey, bool $isDataSetKey = false): void
    {
        $input = [
            'progress' => 1,
            'total' => 2,
            'currentEntity' => 'currentEntity',
            'currentEntityProgress' => 3,
            'exceptionCount' => 0,
            'isAborted' => false,
            'dataSets' => [
                [
                    'entityName' => ProductDefinition::ENTITY_NAME,
                    'total' => 10,
                ],
            ],
        ];

        if ($isDataSetKey) {
            $input['dataSets'][0][$missingKey] = null;
            $expectedPropertyPath = "/someId/dataSets/0/{$missingKey}";
        } else {
            unset($input[$missingKey]);
            $expectedPropertyPath = "/someId/{$missingKey}";
        }

        try {
            $this->serializer->encode(
                new MigrationProgressField('progress', 'progress'),
                new EntityExistence('progress', ['someId' => 'foo'], true, false, false, []),
                new KeyValuePair('someId', $input, false),
                new WriteParameterBag(new SwagMigrationRunDefinition(), WriteContext::createFromContext(Context::createDefaultContext()), '', new WriteCommandQueue())
            )->current();
        } catch (WriteConstraintViolationException $e) {
            $violation = $e->getViolations()->getIterator()->current();

            static::assertSame($expectedPropertyPath, $violation->getPropertyPath());

            return;
        }

        static::fail('No exception was thrown');
    }

    public static function invalidInputProvider(): \Generator
    {
        yield 'Unset progress key of main data' => ['progress'];
        yield 'Unset total key of main data' => ['total'];
        yield 'Unset currentEntity key of main data' => ['currentEntity'];
        yield 'Unset currentEntityProgress key of main data' => ['currentEntityProgress'];
        yield 'Unset dataSets key of main data' => ['dataSets'];

        yield 'Unset entityName key of dataSets' => ['entityName', true];
        yield 'Unset total key of dataSets' => ['total', true];
    }
}
