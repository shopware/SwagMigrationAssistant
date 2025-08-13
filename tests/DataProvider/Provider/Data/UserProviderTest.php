<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\DataProvider\Provider\Data;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\User\UserCollection;
use Shopware\Core\System\User\UserDefinition;
use Shopware\Core\System\User\UserEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\DataProvider\Provider\Data\UserProvider;

class UserProviderTest extends TestCase
{
    public function testGetProvidedDataShouldUnsetPassword(): void
    {
        $context = Context::createDefaultContext();
        $adminUser = new UserEntity();
        $adminUser->setId(Uuid::randomHex());
        $adminUser->setAdmin(true);
        $adminUser->setUsername('admin');
        $adminUser->setEmail('user@example.com');
        $adminUser->setFirstName('');
        $adminUser->setLastName('admin');
        $adminUser->setActive(true);
        $adminUser->setTimeZone('UTC');

        /** @var StaticEntityRepository<UserCollection> $adminUserRepo */
        $adminUserRepo = new StaticEntityRepository([
            new EntitySearchResult(
                UserDefinition::ENTITY_NAME,
                1,
                new UserCollection([$adminUser]),
                null,
                new Criteria(),
                $context
            ),
        ], new UserDefinition());

        $provider = new UserProvider($adminUserRepo);
        $result = $provider->getProvidedData(10, 0, $context);

        static::assertCount(1, $result);
        $userResult = $result[0];
        static::assertIsArray($userResult);

        static::assertSame($adminUser->getId(), $userResult['id']);
        static::assertSame($adminUser->getUsername(), $userResult['username']);
        static::assertArrayNotHasKey('password', $userResult);
    }
}
