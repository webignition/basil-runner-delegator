<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ConsoleOutputFactoryTest extends AbstractBaseTest
{
    private ConsoleOutputFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ConsoleOutputFactory();
    }

    /**
     * @dataProvider createCommentDataProvider
     */
    public function testCreateComment(string $text, string $expectedText)
    {
        $this->assertSame($expectedText, $this->factory->createComment($text));
    }

    public function createCommentDataProvider(): array
    {
        return [
            'empty' => [
                'text' => '',
                'expectedText' => '',
            ],
            'non-empty' => [
                'text' => 'content',
                'expectedText' => "\e[33m" . 'content' . "\e[39m",
            ],
        ];
    }
}
