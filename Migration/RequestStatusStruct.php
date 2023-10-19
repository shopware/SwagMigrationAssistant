<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class RequestStatusStruct extends Struct
{
    public function __construct(
        protected string $code = '',
        protected string $message = 'No error.',
        protected bool $isWarning = false
    ) {
    }

    public function getIsWarning(): bool
    {
        return $this->isWarning;
    }

    public function setIsWarning(bool $isWarning): void
    {
        $this->isWarning = $isWarning;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
