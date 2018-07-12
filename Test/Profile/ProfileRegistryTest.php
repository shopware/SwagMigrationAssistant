<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile;

use Exception;
use SwagMigrationNext\Profile\ProfileNotFoundException;
use SwagMigrationNext\Profile\ProfileRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProfileRegistryTest extends KernelTestCase
{
    /**
     * @var ProfileRegistry
     */
    private $profileRegistry;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->profileRegistry = self::$container->get(ProfileRegistry::class);
    }

    public function testGetProfileNotFound(): void
    {
        try {
            $this->profileRegistry->getProfile('foo');
        } catch (Exception $e) {
            /** @var ProfileNotFoundException $e */
            self::assertInstanceOf(ProfileNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
