<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Validator;

use Shopware\Core\Framework\Context;

interface ValidatorInterface
{
    /**
     * Identifier which internal entity this validator supports
     */
    public function supports(): string;

    /**
     * Validates the converted data of the supported entity type
     */
    public function validateData(array $data, Context $context): void;
}