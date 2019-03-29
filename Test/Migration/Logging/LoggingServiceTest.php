<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Logging;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Logging\SwagMigrationLoggingEntity;

class LoggingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var LoggingService
     */
    private $loggingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var Context
     */
    private $context;

    private static $firstLog = [
        'code' => 'First-Code',
        'title' => 'First-Title',
        'description' => 'First-Description',
        'name' => 'First-Name',
    ];

    private static $secondLog = [
        'code' => 'Second-Code',
        'title' => 'First-Title',
        'description' => 'First-Description',
        'name' => 'First-Name',
    ];

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->loggingService = new LoggingService($this->loggingRepo);
    }

    public function testAddInfo(): void
    {
        $this->loggingService->addInfo(Uuid::randomHex(), self::$firstLog['code'], self::$firstLog['title'], self::$firstLog['description'], ['name' => self::$firstLog['name']]);
        $this->loggingService->addInfo(Uuid::randomHex(), self::$firstLog['code'], self::$secondLog['title'], self::$secondLog['description'], ['name' => self::$secondLog['name']]);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(0, $result->getTotal());

        $this->loggingService->saveLogging($this->context);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        static::assertTrue($this->areLoggingEntriesValid($result->getElements(), LoggingService::INFO_TYPE));
    }

    public function testAddWarning(): void
    {
        $this->loggingService->addWarning(Uuid::randomHex(), self::$firstLog['code'], self::$firstLog['title'], self::$firstLog['description'], ['name' => self::$firstLog['name']]);
        $this->loggingService->addWarning(Uuid::randomHex(), self::$secondLog['code'], self::$secondLog['title'], self::$secondLog['description'], ['name' => self::$secondLog['name']]);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(0, $result->getTotal());

        $this->loggingService->saveLogging($this->context);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        static::assertTrue($this->areLoggingEntriesValid($result->getElements(), LoggingService::WARNING_TYPE));
    }

    public function testAddError(): void
    {
        $this->loggingService->addError(Uuid::randomHex(), self::$firstLog['code'], self::$firstLog['title'], self::$firstLog['description'], ['name' => self::$firstLog['name']]);
        $this->loggingService->addError(Uuid::randomHex(), self::$secondLog['code'], self::$secondLog['title'], self::$firstLog['description'], ['name' => self::$secondLog['name']]);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(0, $result->getTotal());

        $this->loggingService->saveLogging($this->context);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        static::assertTrue($this->areLoggingEntriesValid($result->getElements(), LoggingService::ERROR_TYPE));
    }

    /**
     * @param SwagMigrationLoggingEntity[] $loggins
     */
    private function areLoggingEntriesValid(array $loggins, string $type): bool
    {
        $firstLogValid = false;
        $secondLogValid = false;
        foreach ($loggins as $log) {
            $logEntry = $log->getLogEntry();

            if (
                (isset($logEntry['title']) && $logEntry['title'] === self::$firstLog['title'])
                && (isset($logEntry['details']['name']) && self::$firstLog['name'] === $logEntry['details']['name'])
            ) {
                if (($type === LoggingService::INFO_TYPE || $type === LoggingService::WARNING_TYPE) && (isset($logEntry['description']) && $logEntry['description'] === self::$firstLog['description'])) {
                    $firstLogValid = true;
                }

                if ($type === LoggingService::ERROR_TYPE && (isset($logEntry['code']) && $logEntry['code'] === self::$firstLog['code'])) {
                    $firstLogValid = true;
                }
            }

            if (
                (isset($logEntry['title']) && $logEntry['title'] === self::$secondLog['title'])
                && (isset($logEntry['details']['name']) && self::$secondLog['name'] === $logEntry['details']['name'])
            ) {
                if (($type === LoggingService::INFO_TYPE || $type === LoggingService::WARNING_TYPE) && (isset($logEntry['description']) && $logEntry['description'] === self::$secondLog['description'])) {
                    $secondLogValid = true;
                }

                if ($type === LoggingService::ERROR_TYPE && (isset($logEntry['code']) && $logEntry['code'] === self::$secondLog['code'])) {
                    $secondLogValid = true;
                }
            }
        }

        return $firstLogValid && $secondLogValid;
    }
}
