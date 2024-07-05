<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Run;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Migration\Run\PremappingField;
use SwagMigrationAssistant\Migration\Run\PremappingFieldSerializer;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PremappingFieldSerializerTest extends TestCase
{
    private PremappingFieldSerializer $serializer;

    protected function setUp(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $definitionRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->serializer = new PremappingFieldSerializer($validator, $definitionRegistry);
    }

    public function testEncodeWithStructInput(): void
    {
        $expected = [[
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

        $encoded = $this->serializer->encode(
            new PremappingField('premapping', 'premapping'),
            new EntityExistence('premapping', ['someId' => 'foo'], true, false, false, []),
            new KeyValuePair('someId', [
                new PremappingStruct('entity', [
                    new PremappingEntityStruct('sourceId', 'Mapping #1', 'newId'),
                ], [
                    new PremappingChoiceStruct('newId', 'New ID'),
                ]),
            ], false),
            new WriteParameterBag(
                new SwagMigrationConnectionDefinition(),
                WriteContext::createFromContext(Context::createDefaultContext()),
                '',
                new WriteCommandQueue()
            )
        )->current();

        static::assertSame(\json_encode($expected), $encoded);
    }

    public function testEncodeWithArrayInput(): void
    {
        $expected = [[
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

        $encoded = $this->serializer->encode(
            new PremappingField('premapping', 'premapping'),
            new EntityExistence('premapping', ['someId' => 'foo'], true, false, false, []),
            new KeyValuePair('someId', $expected, false),
            new WriteParameterBag(
                new SwagMigrationConnectionDefinition(),
                WriteContext::createFromContext(Context::createDefaultContext()),
                '',
                new WriteCommandQueue()
            )
        )->current();

        static::assertSame(\json_encode($expected), $encoded);
    }

    public function testDecode(): void
    {
        $expected = [
            new PremappingStruct('entity', [
                new PremappingEntityStruct('sourceId', 'Mapping #1', 'newId'),
            ], [
                new PremappingChoiceStruct('newId', 'New ID'),
            ]),
        ];

        $decoded = $this->serializer->decode(
            new PremappingField('premapping', 'premapping'),
            \json_encode($expected)
        );

        static::assertEquals($expected, $decoded);
    }
}
