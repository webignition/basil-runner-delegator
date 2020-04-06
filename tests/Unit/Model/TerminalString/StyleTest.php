<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\TerminalString;

use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StyleTest extends AbstractBaseTest
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(Style $terminalStringFormat, string $expectedString)
    {
        $this->assertSame($expectedString, (string) $terminalStringFormat);
    }

    public function toStringDataProvider(): array
    {
        return [
            'empty' => [
                'terminalStringFormat' => new Style(),
                'expectedString' => '%s',
            ],
            'foreground colour black' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => Style::COLOUR_BLACK,
                ]),
                'expectedString' => "\033[30m" . '%s' . "\033[0m",
            ],
            'foreground colour red' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => Style::COLOUR_RED,
                ]),
                'expectedString' => "\033[31m" . '%s' . "\033[0m",
            ],
            'foreground colour green' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => Style::COLOUR_GREEN,
                ]),
                'expectedString' => "\033[32m" . '%s' . "\033[0m",
            ],
            'foreground colour yellow' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => Style::COLOUR_YELLOW,
                ]),
                'expectedString' => "\033[33m" . '%s' . "\033[0m",
            ],
            'foreground colour white' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => Style::COLOUR_WHITE,
                ]),
                'expectedString' => "\033[37m" . '%s' . "\033[0m",
            ],
            'foreground colour invalid' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => 'foreground colour invalid',
                ]),
                'expectedString' => '%s',
            ],
            'background colour black' => [
                'terminalStringFormat' => new Style([
                    Style::BACKGROUND_COLOUR => Style::COLOUR_BLACK,
                ]),
                'expectedString' => "\033[40m" . '%s' . "\033[0m",
            ],
            'background colour red' => [
                'terminalStringFormat' => new Style([
                    Style::BACKGROUND_COLOUR => Style::COLOUR_RED,
                ]),
                'expectedString' => "\033[41m" . '%s' . "\033[0m",
            ],
            'background colour yellow' => [
                'terminalStringFormat' => new Style([
                    Style::BACKGROUND_COLOUR => Style::COLOUR_YELLOW,
                ]),
                'expectedString' => "\033[43m" . '%s' . "\033[0m",
            ],
            'bold decoration' => [
                'terminalStringFormat' => new Style([
                    Style::DECORATIONS => [
                        Style::DECORATION_BOLD,
                    ],
                ]),
                'expectedString' => "\033[1m" . '%s' . "\033[0m",
            ],
            'foreground colour, background color, bold decoration' => [
                'terminalStringFormat' => new Style([
                    Style::FOREGROUND_COLOUR => Style::COLOUR_WHITE,
                    Style::BACKGROUND_COLOUR => Style::COLOUR_RED,
                    Style::DECORATIONS => [
                        Style::DECORATION_BOLD,
                    ],
                ]),
                'expectedString' => "\033[37m" . "\033[41m" . "\033[1m" . '%s' . "\033[0m",
            ],
        ];
    }
}
