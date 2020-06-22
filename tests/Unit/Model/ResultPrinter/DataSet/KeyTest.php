<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\DataSet;

use webignition\BasilRunner\Model\ResultPrinter\DataSet\Key;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class KeyTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Key $key, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $key->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'key' => new Key('name'),
                'expectedRenderedString' => '$name',
            ],
        ];
    }
}
