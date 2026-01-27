<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Unit\Migration\Run;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Run\MigrationStep;

#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationStep::class)]
class MigrationStepTest extends TestCase
{
    public function testIsOneOfReturnsTrue(): void
    {
        static::assertTrue(MigrationStep::FETCHING->isOneOf(
            MigrationStep::FETCHING,
            MigrationStep::WRITING,
        ));
    }

    public function testIsOneOfReturnsFalse(): void
    {
        static::assertFalse(MigrationStep::FETCHING->isOneOf(
            MigrationStep::WRITING,
            MigrationStep::MEDIA_PROCESSING
        ));
    }

    public function testIsOneOfWithSingleStep(): void
    {
        static::assertTrue(MigrationStep::ERROR_RESOLUTION->isOneOf(
            MigrationStep::ERROR_RESOLUTION
        ));
    }

    public function testAssertOneOfDoesNotThrowWhenStepMatches(): void
    {
        static::expectNotToPerformAssertions();

        MigrationStep::FETCHING->assertOneOf(
            MigrationStep::FETCHING,
            MigrationStep::WRITING
        );
    }

    public function testAssertOneOfThrowsWhenStepDoesNotMatch(): void
    {
        static::expectExceptionObject(MigrationException::migrationNotInStep('fetching, writing'));

        MigrationStep::IDLE->assertOneOf(
            MigrationStep::FETCHING,
            MigrationStep::WRITING
        );
    }

    public function testAssertOneOfWithSingleAllowedStep(): void
    {
        static::expectNotToPerformAssertions();

        MigrationStep::WAITING_FOR_APPROVE->assertOneOf(MigrationStep::WAITING_FOR_APPROVE);
    }

    #[DataProvider('provideAbortableSteps')]
    public function testAbortableStepsValidation(MigrationStep $step, bool $shouldPass): void
    {
        $abortableSteps = [
            MigrationStep::FETCHING,
            MigrationStep::ERROR_RESOLUTION,
            MigrationStep::WRITING,
            MigrationStep::MEDIA_PROCESSING,
        ];

        if ($shouldPass) {
            $step->assertOneOf(...$abortableSteps);
            static::assertTrue(true);
        } else {
            $this->expectException(MigrationException::class);
            $step->assertOneOf(...$abortableSteps);
        }
    }

    /**
     * @return iterable<string, array{MigrationStep, bool}>
     */
    public static function provideAbortableSteps(): iterable
    {
        yield 'FETCHING is abortable' => [MigrationStep::FETCHING, true];
        yield 'ERROR_RESOLUTION is abortable' => [MigrationStep::ERROR_RESOLUTION, true];
        yield 'WRITING is abortable' => [MigrationStep::WRITING, true];
        yield 'MEDIA_PROCESSING is abortable' => [MigrationStep::MEDIA_PROCESSING, true];
        yield 'IDLE is not abortable' => [MigrationStep::IDLE, false];
        yield 'CLEANUP is not abortable' => [MigrationStep::CLEANUP, false];
        yield 'INDEXING is not abortable' => [MigrationStep::INDEXING, false];
        yield 'WAITING_FOR_APPROVE is not abortable' => [MigrationStep::WAITING_FOR_APPROVE, false];
        yield 'ABORTING is not abortable' => [MigrationStep::ABORTING, false];
        yield 'FINISHED is not abortable' => [MigrationStep::FINISHED, false];
        yield 'ABORTED is not abortable' => [MigrationStep::ABORTED, false];
    }

    public function testNeedsProcessorUsesIsOneOf(): void
    {
        // MANUAL_STEPS
        static::assertFalse(MigrationStep::ERROR_RESOLUTION->needsProcessor());
        static::assertFalse(MigrationStep::WAITING_FOR_APPROVE->needsProcessor());

        // non MANUAL_STEPS
        static::assertTrue(MigrationStep::FETCHING->needsProcessor());
        static::assertTrue(MigrationStep::WRITING->needsProcessor());
        static::assertTrue(MigrationStep::MEDIA_PROCESSING->needsProcessor());
    }
}
