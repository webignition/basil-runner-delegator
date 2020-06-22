<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\ObjectReflector\ObjectReflector;

class RenderableCollectionTest extends AbstractBaseTest
{
    public function testAppend()
    {
        $collection = new RenderableCollection([]);
        $this->assertEquals([], ObjectReflector::getProperty($collection, 'items'));

        $item1 = new Literal('item1');
        $collection = $collection->append($item1);
        $this->assertEquals([$item1], ObjectReflector::getProperty($collection, 'items'));

        $item2 = new Literal('item2');
        $collection = $collection->append($item2);
        $this->assertEquals([$item1, $item2], ObjectReflector::getProperty($collection, 'items'));
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(RenderableCollection $collection, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $collection->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'single literal' => [
                'collection' => new RenderableCollection([
                    new Literal('content'),
                ]),
                'expectedRenderedString' => 'content',
            ],
            'multiple literals' => [
                'collection' => new RenderableCollection([
                    new Literal('line1'),
                    new Literal('line2'),
                    new Literal('line3'),
                ]),
                'expectedRenderedString' =>
                    'line1' . "\n" .
                    'line2' . "\n" .
                    'line3'
                ,
            ],
        ];
    }
}
