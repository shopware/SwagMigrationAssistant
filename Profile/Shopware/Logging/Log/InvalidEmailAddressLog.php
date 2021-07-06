<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

class InvalidEmailAddressLog extends BaseRunLogEntry
{
    /**
     * @var string
     */
    private $email;

    public function __construct(string $runId, string $entity, string $sourceId, string $email)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->email = $email;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__INVALID_EMAIL_ADDRESS';
    }

    public function getTitle(): string
    {
        return 'Invalid Email address';
    }

    public function getParameters(): array
    {
        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'email' => $this->email,
        ];
    }

    public function getDescription(): string
    {
        $args = $this->getParameters();

        return \sprintf(
            '%s with source id "%s" could not be converted because of invalid email address: %s.',
            $args['entity'],
            $args['sourceId'],
            $args['email']
        );
    }
}
