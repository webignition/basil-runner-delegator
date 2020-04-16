<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class SummaryLine extends ActivityLine
{
    public function __construct(string $content)
    {
        parent::__construct('*', $content);
    }
}
