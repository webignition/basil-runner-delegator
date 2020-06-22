<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\IndentedContent;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class IndentedContentTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(IndentedContent $indentedContent, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $indentedContent->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'literal, single line' => [
                'indentedContent' => new IndentedContent(new Literal('content')),
                'expectedRenderedString' => '  content',
            ],
            'literal, multi-line' => [
                'indentedContent' => new IndentedContent(new Literal(
                    'line1' . "\n" .
                    'line2' . "\n" .
                    "\n" .
                    'line4'
                )),
                'expectedRenderedString' =>
                    '  line1' . "\n" .
                    '  line2' . "\n" .
                    "\n" .
                    '  line4'
                ,
            ],
        ];
    }
}
