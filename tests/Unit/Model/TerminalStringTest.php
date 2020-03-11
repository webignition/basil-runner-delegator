<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model;

use webignition\BasilRunner\Model\TerminalString;
use webignition\BasilRunner\Model\TerminalStringFormat;
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
            'empty, formatting is ignored' => [
                'terminalString' => new TerminalString(
                    '',
                    new TerminalStringFormat([
                        TerminalStringFormat::FOREGROUND_COLOUR => TerminalStringFormat::COLOUR_RED,
                    ])
                ),
                'expectedString' => '',
            ],
            'non-empty, no formatting' => [
                'terminalString' => new TerminalString('content'),
                'expectedString' => 'content',
            ],
            'non-empty, has format' => [
                'terminalString' => new TerminalString(
                    'content',
                    new TerminalStringFormat([
                        TerminalStringFormat::FOREGROUND_COLOUR => TerminalStringFormat::COLOUR_WHITE,
                        TerminalStringFormat::BACKGROUND_COLOUR => TerminalStringFormat::COLOUR_RED,
                        TerminalStringFormat::DECORATIONS => [
                            TerminalStringFormat::DECORATION_BOLD,
                        ],
                    ])
                ),
                'expectedString' => "\033[37m" . "\033[41m" . "\033[1m" . 'content' . "\033[0m",
            ],
        ];
    }
}
