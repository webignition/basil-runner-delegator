<?php

namespace webignition\BasilRunner\Tests\Model;

class PhpUnitOutput
{
    private string $body;

    public function __construct(string $content)
    {
        $contentLines = explode("\n", $content);

        array_shift($contentLines);
        array_shift($contentLines);

        $this->body = implode("\n", $contentLines);
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
