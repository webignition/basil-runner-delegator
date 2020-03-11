<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model;

use webignition\BasilRunner\Model\TerminalString;
use webignition\BasilRunner\Model\TerminalStringFormat;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class TerminalStringFormatTest extends AbstractBaseTest
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(TerminalStringFormat $terminalStringFormat, string $expectedString)
    {
        $this->assertSame($expectedString, (string) $terminalStringFormat);
    }

    public function toStringDataProvider(): array
    {
        return [
            'empty' => [
                'terminalStringFormat' => new TerminalStringFormat(),
                'expectedString' => '%s',
            ],
            'non-empty, foreground colour red' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withForegroundColour(TerminalString::COLOUR_RED),
                'expectedString' => "\033[31m" . '%s' . "\033[0m",
            ],
            'non-empty, foreground colour green' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withForegroundColour(TerminalString::COLOUR_GREEN),
                'expectedString' => "\033[32m" . '%s' . "\033[0m",
            ],
            'non-empty, foreground colour white' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withForegroundColour(TerminalString::COLOUR_WHITE),
                'expectedString' => "\033[37m" . '%s' . "\033[0m",
            ],
            'non-empty, foreground colour invalid' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withForegroundColour('undefined colour'),
                'expectedString' => '%s',
            ],
            'non-empty, background colour black' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withBackgroundColour(TerminalString::COLOUR_BLACK),
                'expectedString' => "\033[40m" . '%s' . "\033[0m",
            ],
            'non-empty, background colour red' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withBackgroundColour(TerminalString::COLOUR_RED),
                'expectedString' => "\033[41m" . '%s' . "\033[0m",
            ],
            'non-empty, bold decoration' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withDecoration(TerminalString::DECORATION_BOLD),
                'expectedString' => "\033[1m" . '%s' . "\033[0m",
            ],
            'non-empty, foreground colour, background color, bold decoration' => [
                'terminalStringFormat' => (new TerminalStringFormat())
                    ->withForegroundColour(TerminalString::COLOUR_WHITE)
                    ->withBackgroundColour(TerminalString::COLOUR_RED)
                    ->withDecoration(TerminalString::DECORATION_BOLD),
                'expectedString' => "\033[37m" . "\033[41m" . "\033[1m" . '%s' . "\033[0m",
            ],
        ];
    }
}
