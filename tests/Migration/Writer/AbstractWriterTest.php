<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Writer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;

#[Package('services-settings')]
class AbstractWriterTest extends TestCase
{
    private AbstractWriter $abstractWriter;

    private array $dataToWrite;

    private Context $context;

    /**
     * @var MockObject|EntityWriterInterface
     */
    private EntityWriterInterface $entityWriter;

    /**
     * @var MockObject|EntityDefinition
     */
    private EntityDefinition $entityDefinition;

    private array $writeResult;

    protected function setUp(): void
    {
        $this->entityWriter = $this->createMock(EntityWriterInterface::class);
        $this->entityDefinition = $this->createMock(EntityDefinition::class);
        $this->abstractWriter = $this->getMockForAbstractClass(AbstractWriter::class, [
            $this->entityWriter,
            $this->entityDefinition,
        ]);
        $this->dataToWrite = [];
        $this->context = Context::createDefaultContext();
        $this->writeResult = [];
        $this->entityWriter->method('upsert')->willReturnReference($this->writeResult);
    }

    public function testWriteDataPassDataToBeWrittenToEntityWriter(): void
    {
        $this->dataToWrite = ['test' => '1234'];

        $this->entityWriter
            ->expects(static::once())
            ->method('upsert')
            ->with(
                $this->entityDefinition,
                ['test' => '1234'],
                WriteContext::createFromContext($this->context)
            );

        $this->abstractWriter->writeData($this->dataToWrite, $this->context);
    }

    public function testWriteDataReturnWriteResult(): void
    {
        $this->writeResult = ['test' => '1234'];

        $result = $this->abstractWriter->writeData($this->dataToWrite, $this->context);

        static::assertEquals(['test' => '1234'], $result);
    }
}
