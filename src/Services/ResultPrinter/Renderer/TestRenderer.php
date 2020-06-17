<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\TestName;
use webignition\BasilRunner\Model\TestOutput\Test;

class TestRenderer
{
    /**
     * @param Test $test
     *
     * @return RenderableInterface
     */
    public function render(Test $test): RenderableInterface
    {
        return new TestName($test->getRelativePath());
    }
}
