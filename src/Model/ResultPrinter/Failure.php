<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class Failure extends AbstractEncapsulatedContentLine
{
    public const START = '<failure>';
    public const END = '</failure>';

    public function __construct(string $content)
    {
        parent::__construct($content);
    }

    protected function getRenderTemplate(): string
    {
        return self::START . '%s' . self::END;
    }
}
