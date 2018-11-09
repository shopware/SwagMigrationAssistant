<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Services;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationNext\Gateway\Shopware55\Api\Shopware55ApiGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class MigrationProfileUuidService
{
    /**
     * @var string
     */
    private $profileUuid;

    /**
     * @var RepositoryInterface
     */
    private $profileRepository;

    /**
     * @var string
     */
    private $profile;

    /**
     * @var string
     */
    private $gateway;

    public function __construct(RepositoryInterface $profileRepository, $profile = Shopware55Profile::PROFILE_NAME, $gateway = Shopware55ApiGateway::GATEWAY_TYPE)
    {
        $this->profileRepository = $profileRepository;
        $this->profile = $profile;
        $this->gateway = $gateway;
        $this->setProfileUuid();
    }

    public function getProfileUuid(): string
    {
        return $this->profileUuid;
    }

    private function setProfileUuid()
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', $this->profile));
        $criteria->addFilter(new EqualsFilter('gateway', $this->gateway));
        $profileResult = $this->profileRepository->search($criteria, Context::createDefaultContext(Defaults::TENANT_ID));
        $profileIds = $profileResult->getIds();

        $this->profileUuid = array_pop($profileIds);
    }
}
