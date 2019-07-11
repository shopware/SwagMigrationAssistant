<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Profile\Dummy;

use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

class DummyProfile implements ProfileInterface
{
    public const PROFILE_NAME = 'dummy';

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function getSourceSystemName(): string
    {
        return 'unknown';
    }

    public function getVersion(): string
    {
        return '1.0';
    }
}
