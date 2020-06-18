<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\DataSet;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class KeyValue implements RenderableInterface
{
    use IndentTrait;

    private Key $key;
    private Comment $value;

    public function __construct(string $key, string $value)
    {
        $this->key = new Key($key);
        $this->value = new Comment($value);
    }

    public function render(): string
    {
        return sprintf(
            '%s%s: %s',
            $this->createIndentContent(3),
            $this->key->render(),
            $this->value->render()
        );
    }
}
