<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Unit\Migration\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use SwagMigrationAssistant\Migration\Validation\ExternalResourceValidator;

#[Package('fundamentals@after-sales')]
#[CoversClass(ExternalResourceValidator::class)]
class ExternalResourceValidatorTest extends TestCase
{
    private ExternalResourceValidator $validator;

    private string $root;

    protected function setUp(): void
    {
        $this->validator = new ExternalResourceValidator(
            ['jpg', 'jpeg', 'png', 'pdf', 'mp4'],
            ['zip', 'xml', 'exe'],
        );

        $this->root = sys_get_temp_dir();
    }

    public function testValidPathWithinRootPasses(): void
    {
        static::expectNotToPerformAssertions();

        $this->validator->validatePath('media/image.jpg', $this->root);
    }

    public function testValidPathWithoutRootPasses(): void
    {
        static::expectNotToPerformAssertions();

        $this->validator->validatePath('some/nested/file.pdf');
    }

    public function testExtensionFromPrivateAllowlistPasses(): void
    {
        static::expectNotToPerformAssertions();

        $this->validator->validatePath('archive.zip', $this->root);
    }

    public function testExtensionMatchIsCaseInsensitive(): void
    {
        static::expectNotToPerformAssertions();

        $this->validator->validatePath('image.JPG', $this->root);
        $this->validator->validatePath('image.Jpg', $this->root);
    }

    public function testNoRootSkipsTraversalCheck(): void
    {
        static::expectNotToPerformAssertions();

        $this->validator->validatePath('subfolder/image.jpg');
    }

    /**
     * @return array<string, array{path: string, root: string|null, violation: string}>
     */
    public static function invalidPathProvider(): array
    {
        $root = sys_get_temp_dir();

        return [
            'null byte' => [
                'path' => "image\0.jpg",
                'root' => null,
                'violation' => 'null byte detected',
            ],
            'percent-encoded slash' => [
                'path' => 'media%2Fimage.jpg',
                'root' => null,
                'violation' => 'percent-encoded characters are not allowed',
            ],
            'percent-encoded dot-dot' => [
                'path' => '..%2Fetc%2Fpasswd.jpg',
                'root' => null,
                'violation' => 'percent-encoded characters are not allowed',
            ],
            'absolute unix path' => [
                'path' => '/etc/passwd.jpg',
                'root' => null,
                'violation' => 'absolute paths are not allowed',
            ],
            'absolute windows path' => [
                'path' => 'C:\\Windows\\system32\\file.jpg',
                'root' => null,
                'violation' => 'absolute paths are not allowed',
            ],
            'traversal with root' => [
                'path' => '../../etc/passwd.jpg',
                'root' => $root,
                'violation' => 'path traversal detected',
            ],
            'deep traversal with root' => [
                'path' => 'subdir/../../../etc/passwd.jpg',
                'root' => $root,
                'violation' => 'path traversal detected',
            ],
            'disallowed extension php' => [
                'path' => 'shell.php',
                'root' => null,
                'violation' => 'file extension not allowed',
            ],
            'disallowed extension phar' => [
                'path' => 'payload.phar',
                'root' => null,
                'violation' => 'file extension not allowed',
            ],
            'no extension' => [
                'path' => 'justfilename',
                'root' => null,
                'violation' => 'file extension not allowed',
            ],
        ];
    }

    #[DataProvider('invalidPathProvider')]
    public function testInvalidPathThrows(string $path, ?string $root, string $violation): void
    {
        static::expectExceptionObject(MigrationValidationException::invalidExternalPath($path, $violation));

        $this->validator->validatePath($path, $root);
    }
}
