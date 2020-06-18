<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\DataSet;

use webignition\BasilRunner\Model\ResultPrinter\DataSet\KeyValue;
use webignition\BasilRunner\Model\ResultPrinter\DataSet\KeyValueCollection;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class KeyValueCollectionTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(KeyValueCollection $collection, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $collection->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'empty' => [
                'collection' => new KeyValueCollection([]),
                'expectedRenderedString' => '',
            ],
            'non-empty' => [
                'collection' => new KeyValueCollection([
                    new KeyValue('key1', 'value1'),
                    new KeyValue('key2', 'value2'),
                ]),
                'expectedRenderedString' =>
                    '      $key1: <comment>value1</comment>' . "\n" .
                    '      $key2: <comment>value2</comment>'
                ,
            ],
        ];
    }
}
