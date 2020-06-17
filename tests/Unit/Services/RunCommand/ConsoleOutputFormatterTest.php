<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\RunCommand;

use webignition\BasilRunner\Model\ResultPrinter\TestName;
use webignition\BasilRunner\Services\RunCommand\ConsoleOutputFormatter;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ConsoleOutputFormatterTest extends AbstractBaseTest
{
    private ConsoleOutputFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new ConsoleOutputFormatter();
    }

    /**
     * @dataProvider formatDataProvider
     */
    public function testFormat(string $line, string $expectedFormattedString)
    {
        $this->assertSame($expectedFormattedString, $this->formatter->format($line));
    }

    public function formatDataProvider(): array
    {
        return [
            'test name' => [
                'line' => (new TestName('test.yml'))->render(),
                'expectedFormattedString' => '<options=bold>test.yml</>',
            ],
        ];
    }
}
