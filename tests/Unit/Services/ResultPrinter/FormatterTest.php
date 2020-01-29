<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BasilRunner\Services\ResultPrinter\Formatter;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class FormatterTest extends AbstractBaseTest
{
    /**
     * @dataProvider colouriseDataProvider
     */
    public function testColourise(string $content, string $colour, string $expectedColouredContent)
    {
        $formatter = Formatter::create();

        $this->assertSame($expectedColouredContent, $formatter->colourise($content, $colour));
    }

    public function colouriseDataProvider(): array
    {
        return [
            'red' => [
                'content' => 'this is to be red',
                'colour' => Formatter::COLOUR_FG_RED,
                'expectedColouredContent' => "\033[31mthis is to be red\033[0m",
            ],
            'green' => [
                'content' => 'this is to be green',
                'colour' => Formatter::COLOUR_FG_GREEN,
                'expectedColouredContent' => "\033[32mthis is to be green\033[0m",
            ],
            'unknown' => [
                'content' => 'this is to be purple?',
                'colour' => 'purple?',
                'expectedColouredContent' => 'this is to be purple?',
            ],
        ];
    }
}
