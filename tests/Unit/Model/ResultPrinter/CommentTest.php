<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class CommentTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Comment $comment, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $comment->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'comment' => new Comment('content'),
                'expectedRenderedString' => '<comment>content</comment>',
            ],
        ];
    }
}
