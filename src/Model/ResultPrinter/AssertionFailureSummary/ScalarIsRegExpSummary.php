<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class ScalarIsRegExpSummary implements RenderableInterface
{
    private Comment $regexp;

    public function __construct(string $regexp)
    {
        $this->regexp = new Comment($regexp);
    }

    public function render(): string
    {
        return sprintf(
            '* %s %s',
            $this->regexp->render(),
            'is not a valid regular expression'
        );
    }
}
