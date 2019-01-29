<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Services;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class MigrationProfileUuidService
{
    /**
     * @var string
     */
    private $profileUuid;

    /**
     * @var EntityRepositoryInterface
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

    public function __construct(
        EntityRepositoryInterface $profileRepository,
        $profile = Shopware55Profile::PROFILE_NAME,
        $gateway = Shopware55ApiGateway::GATEWAY_TYPE
    ) {
        $this->profileRepository = $profileRepository;
        $this->profile = $profile;
        $this->gateway = $gateway;
        $this->setProfileUuid();
    }

    public function getProfileUuid(): string
    {
        return $this->profileUuid;
    }

    private function setProfileUuid(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $this->profile));
        $criteria->addFilter(new EqualsFilter('gatewayName', $this->gateway));
        $profileResult = $this->profileRepository->search($criteria, Context::createDefaultContext());
        /** @var $profile SwagMigrationProfileEntity */
        $profile = $profileResult->first();
        $this->profileUuid = $profile->getId();
    }
}
