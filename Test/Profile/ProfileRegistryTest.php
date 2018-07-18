<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile;

use Exception;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Profile\ProfileNotFoundException;
use SwagMigrationNext\Profile\ProfileRegistry;
use SwagMigrationNext\Profile\ProfileRegistryInterface;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Profile\Dummy\DummyProfile;
use Symfony\Component\HttpFoundation\Response;

class ProfileRegistryTest extends TestCase
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    protected function setUp()
    {
        $this->profileRegistry = new ProfileRegistry(new DummyCollection([new DummyProfile()]));
    }

    public function testGetProfileNotFound(): void
    {
        try {
            $this->profileRegistry->getProfile('foo');
        } catch (Exception $e) {
            /* @var ProfileNotFoundException $e */
            self::assertInstanceOf(ProfileNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
