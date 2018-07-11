<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile;

use SwagMigrationNext\Profile\ProfileNotFoundException;
use SwagMigrationNext\Profile\ProfileRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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

    public function testGetProfileNotFound()
    {
        $this->expectException(ProfileNotFoundException::class);
        $this->profileRegistry->getProfile('foo');
    }
}
