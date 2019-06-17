<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

interface ProfileInterface
{
    /**
     * Identifier for the profile
     */
    public function getName(): string;

    public function getSourceSystemName(): string;

    public function getVersion(): string;
}
