<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class MediaFolderDataSet extends Shopware55DataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::MEDIA_FOLDER;
    }

    public function supports(string $profileName): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationMediaAlbums';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
