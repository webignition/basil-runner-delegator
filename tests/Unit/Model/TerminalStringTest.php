<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model;

use webignition\BasilRunner\Model\TerminalString;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class TerminalStringTest extends AbstractBaseTest
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(TerminalString $terminalString, string $expectedString)
    {
        $this->assertSame($expectedString, (string) $terminalString);
    }

    public function toStringDataProvider(): array
    {
        return [
            'empty' => [
                'terminalString' => new TerminalString(''),
                'expectedString' => '',
            ],
            'empty, foreground colour is ignored' => [
                'terminalString' => (new TerminalString(''))
                    ->withForegroundColour(TerminalString::COLOUR_RED),
                'expectedString' => '',
            ],
            'empty, background colour is ignored' => [
                'terminalString' => (new TerminalString(''))
                    ->withBackgroundColour(TerminalString::COLOUR_RED),
                'expectedString' => '',
            ],
            'empty, decorations are ignored' => [
                'terminalString' => (new TerminalString(''))
                    ->withDecoration(TerminalString::DECORATION_BOLD),
                'expectedString' => '',
            ],
            'non-empty, no colour, no decorations' => [
                'terminalString' => new TerminalString('content'),
                'expectedString' => 'content',
            ],
            'non-empty, foreground colour red' => [
                'terminalString' => (new TerminalString('content'))
                    ->withForegroundColour(TerminalString::COLOUR_RED),
                'expectedString' => "\033[31m" . 'content' . "\033[0m",
            ],
            'non-empty, foreground colour green' => [
                'terminalString' => (new TerminalString('content'))
                    ->withForegroundColour(TerminalString::COLOUR_GREEN),
                'expectedString' => "\033[32m" . 'content' . "\033[0m",
            ],
            'non-empty, foreground colour white' => [
                'terminalString' => (new TerminalString('content'))
                    ->withForegroundColour(TerminalString::COLOUR_WHITE),
                'expectedString' => "\033[37m" . 'content' . "\033[0m",
            ],
            'non-empty, foreground colour invalid' => [
                'terminalString' => (new TerminalString('content'))
                    ->withForegroundColour('undefined colour'),
                'expectedString' => 'content',
            ],
            'non-empty, background colour black' => [
                'terminalString' => (new TerminalString('content'))
                    ->withBackgroundColour(TerminalString::COLOUR_BLACK),
                'expectedString' => "\033[40m" . 'content' . "\033[0m",
            ],
            'non-empty, background colour red' => [
                'terminalString' => (new TerminalString('content'))
                    ->withBackgroundColour(TerminalString::COLOUR_RED),
                'expectedString' => "\033[41m" . 'content' . "\033[0m",
            ],
            'non-empty, bold decoration' => [
                'terminalString' => (new TerminalString('content'))
                    ->withDecoration(TerminalString::DECORATION_BOLD),
                'expectedString' => "\033[1m" . 'content' . "\033[0m",
            ],
            'non-empty, foreground colour, background color, bold decoration' => [
                'terminalString' => (new TerminalString('content'))
                    ->withForegroundColour(TerminalString::COLOUR_WHITE)
                    ->withBackgroundColour(TerminalString::COLOUR_RED)
                    ->withDecoration(TerminalString::DECORATION_BOLD),
                'expectedString' => "\033[37m" . "\033[41m" . "\033[1m" . 'content' . "\033[0m",
            ],
        ];
    }
}
