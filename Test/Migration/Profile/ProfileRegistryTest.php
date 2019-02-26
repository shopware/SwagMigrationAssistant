<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Profile;

use Exception;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\ProfileNotFoundException;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Profile\Dummy\DummyProfile;
use Symfony\Component\HttpFoundation\Response;

class ProfileRegistryTest extends TestCase
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    protected function setUp(): void
    {
        $this->profileRegistry = new ProfileRegistry(new DummyCollection([new DummyProfile()]));
    }

    public function testGetProfileNotFound(): void
    {
        try {
            $this->profileRegistry->getProfile('foo');
        } catch (Exception $e) {
            /* @var ProfileNotFoundException $e */
            static::assertInstanceOf(ProfileNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
