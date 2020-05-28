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
     * @dataProvider createTestPathDataProvider
     */
    public function testCreateTestPath(string $text, string $expectedText)
    {
        $this->assertSame($expectedText, $this->factory->createTestPath($text));
    }

    public function createTestPathDataProvider(): array
    {
        return [
            'empty' => [
                'text' => '',
                'expectedText' => '',
            ],
            'non-empty' => [
                'text' => 'test.yml',
                'expectedText' => "\e[1m" . 'test.yml' . "\e[22m",
            ],
        ];
    }

    /**
     * @dataProvider createSuccessDataProvider
     */
    public function testCreateSuccess(string $text, string $expectedText)
    {
        $this->assertSame($expectedText, $this->factory->createSuccess($text));
    }

    public function createSuccessDataProvider(): array
    {
        return [
            'empty' => [
                'text' => '',
                'expectedText' => '',
            ],
            'non-empty' => [
                'text' => 'content',
                'expectedText' => "\e[32m" . 'content' . "\e[39m",
            ],
        ];
    }

    /**
     * @dataProvider createFailureDataProvider
     */
    public function testCreateFailure(string $text, string $expectedText)
    {
        $this->assertSame($expectedText, $this->factory->createFailure($text));
    }

    public function createFailureDataProvider(): array
    {
        return [
            'empty' => [
                'text' => '',
                'expectedText' => '',
            ],
            'non-empty' => [
                'text' => 'content',
                'expectedText' => "\e[31m" . 'content' . "\e[39m",
            ],
        ];
    }

    /**
     * @dataProvider createHighlightedFailureDataProvider
     */
    public function testCreateHighlightedFailure(string $text, string $expectedText)
    {
        $this->assertSame($expectedText, $this->factory->createHighlightedFailure($text));
    }

    public function createHighlightedFailureDataProvider(): array
    {
        return [
            'empty' => [
                'text' => '',
                'expectedText' => '',
            ],
            'non-empty' => [
                'text' => 'content',
                'expectedText' => "\e[37;41m" . 'content' . "\e[39;49m",
            ],
        ];
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
