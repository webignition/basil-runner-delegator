<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\TestOutputRenderer;

use Facebook\WebDriver\Exception\InvalidSelectorException;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\ExceptionRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class ExceptionRendererTest extends AbstractBaseTest
{
    private ExceptionRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new ExceptionRenderer();
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(\Throwable $exception, string $expectedRenderedException)
    {
        $this->assertSame($expectedRenderedException, $this->renderer->render($exception));
    }

    public function renderDataProvider(): array
    {
        return [
            'InvalidLocatorException: CSS selector' => [
                'exception' => new InvalidLocatorException(
                    new ElementIdentifier('a[href=https://example.com]'),
                    \Mockery::mock(InvalidSelectorException::class)
                ),
                'expectedRenderedException' =>
                    'CSS selector <comment>a[href=https://example.com]</comment> is not valid'
                ,
            ],
            'InvalidLocatorException: XPath expression' => [
                'exception' => new InvalidLocatorException(
                    new ElementIdentifier('//?'),
                    \Mockery::mock(InvalidSelectorException::class)
                ),
                'expectedRenderedException' =>
                    'XPath expression <comment>//?</comment> is not valid'
                ,
            ],
            'unknown exception' => [
                'exception' => new \LogicException('logic exception message'),
                'expectedRenderedException' =>
                    'An unknown exception has occurred:' . "\n" .
                    '    - LogicException' . "\n" .
                    '    - logic exception message'
                ,
            ],
        ];
    }
}
