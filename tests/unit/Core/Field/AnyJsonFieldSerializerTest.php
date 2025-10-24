<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Core\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Core\Field\AnyJsonField;
use SwagMigrationAssistant\Core\Field\AnyJsonFieldSerializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(AnyJsonFieldSerializer::class)]
class AnyJsonFieldSerializerTest extends TestCase
{
    private readonly AnyJsonFieldSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new AnyJsonFieldSerializer(
            $this->createMock(ValidatorInterface::class),
            $this->createMock(DefinitionInstanceRegistry::class),
        );
    }

    public function testEncodeExpectException(): void
    {
        $field = new JsonField('storage_name', 'propertyName');
        $existence = $this->createMock(EntityExistence::class);
        $keyValuePair = $this->createMock(KeyValuePair::class);
        $writeParameters = $this->createMock(WriteParameterBag::class);

        $this->expectExceptionObject(DataAbstractionLayerException::invalidSerializerField(AnyJsonField::class, $field));

        $this->serializer->encode($field, $existence, $keyValuePair, $writeParameters)->current();
    }

    public function testDecodeExpectException(): void
    {
        $field = new JsonField('storage_name', 'propertyName');

        $this->expectExceptionObject(DataAbstractionLayerException::invalidSerializerField(AnyJsonField::class, $field));

        $this->serializer->decode($field, '"any"');
    }

    #[DataProvider('encodeData')]
    public function testEncode(mixed $value, ?string $expected): void
    {
        $field = new AnyJsonField('storage_name', 'propertyName');
        $existence = new EntityExistence('entityName', [], true, false, false, []);
        $keyValuePair = new KeyValuePair('propertyName', $value, true, false);
        $writeParameters = $this->createMock(WriteParameterBag::class);

        $result = $this->serializer->encode($field, $existence, $keyValuePair, $writeParameters);

        static::assertSame($expected, $result->current());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function encodeData(): array
    {
        return [
            'null' => [
                'value' => null,
                'expected' => 'null',
            ],
            'boolean' => [
                'value' => true,
                'expected' => 'true',
            ],
            'integer' => [
                'value' => 1,
                'expected' => '1',
            ],
            'float' => [
                'value' => 1.1,
                'expected' => '1.1',
            ],
            'string' => [
                'value' => 'test',
                'expected' => '"test"',
            ],
            'array' => [
                'value' => ['test' => 'testValue', 'nested' => ['test' => 'nestedTestValue']],
                'expected' => '{"test":"testValue","nested":{"test":"nestedTestValue"}}',
            ],
        ];
    }

    #[DataProvider('decodeData')]
    public function testDecode(string $value, mixed $expected): void
    {
        $field = new AnyJsonField('storage_name', 'propertyName');

        $result = $this->serializer->decode($field, $value);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function decodeData(): array
    {
        return [
            'null' => [
                'value' => 'null',
                'expected' => null,
            ],
            'boolean' => [
                'value' => 'false',
                'expected' => false,
            ],
            'integer' => [
                'value' => '12',
                'expected' => 12,
            ],
            'float' => [
                'value' => '45.1',
                'expected' => 45.1,
            ],
            'string' => [
                'value' => '"FooBar"',
                'expected' => 'FooBar',
            ],
            'array' => [
                'value' => '{"test":"testValue","nested":{"test":"nestedTestValue"}}',
                'expected' => ['test' => 'testValue', 'nested' => ['test' => 'nestedTestValue']],
            ],
        ];
    }
}
