<?php

namespace webignition\BasilRunner\Tests\Model;

class PhpUnitOutput
{
    /**
     * @var string
     */
    private $header = '';

    /**
     * @var string
     */
    private $body = '';

    public function __construct(string $content)
    {
        $contentLines = explode("\n", $content);

        $this->header = (string) $contentLines[0];

        array_shift($contentLines);
        array_shift($contentLines);

        $this->body = implode("\n", $contentLines);
    }

    public function getBody(): string
    {
        return $this->body;
    }
}