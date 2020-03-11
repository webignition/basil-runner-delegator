<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model;

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
            'foreground colour red' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::FOREGROUND_COLOUR => TerminalStringFormat::COLOUR_RED,
                ]),
                'expectedString' => "\033[31m" . '%s' . "\033[0m",
            ],
            'foreground colour green' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::FOREGROUND_COLOUR => TerminalStringFormat::COLOUR_GREEN,
                ]),
                'expectedString' => "\033[32m" . '%s' . "\033[0m",
            ],
            'foreground colour white' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::FOREGROUND_COLOUR => TerminalStringFormat::COLOUR_WHITE,
                ]),
                'expectedString' => "\033[37m" . '%s' . "\033[0m",
            ],
            'foreground colour invalid' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::FOREGROUND_COLOUR => 'foreground colour invalid',
                ]),
                'expectedString' => '%s',
            ],
            'background colour black' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::BACKGROUND_COLOUR => TerminalStringFormat::COLOUR_BLACK,
                ]),
                'expectedString' => "\033[40m" . '%s' . "\033[0m",
            ],
            'background colour red' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::BACKGROUND_COLOUR => TerminalStringFormat::COLOUR_RED,
                ]),
                'expectedString' => "\033[41m" . '%s' . "\033[0m",
            ],
            'bold decoration' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::DECORATIONS => [
                        TerminalStringFormat::DECORATION_BOLD,
                    ],
                ]),
                'expectedString' => "\033[1m" . '%s' . "\033[0m",
            ],
            'foreground colour, background color, bold decoration' => [
                'terminalStringFormat' => new TerminalStringFormat([
                    TerminalStringFormat::FOREGROUND_COLOUR => TerminalStringFormat::COLOUR_WHITE,
                    TerminalStringFormat::BACKGROUND_COLOUR => TerminalStringFormat::COLOUR_RED,
                    TerminalStringFormat::DECORATIONS => [
                        TerminalStringFormat::DECORATION_BOLD,
                    ],
                ]),
                'expectedString' => "\033[37m" . "\033[41m" . "\033[1m" . '%s' . "\033[0m",
            ],
        ];
    }
}
