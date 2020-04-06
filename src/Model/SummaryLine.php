<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilRunner\Model\TerminalString\TerminalString;

class SummaryLine extends ActivityLine
{
    public function __construct(TerminalString $content)
    {
        parent::__construct(new TerminalString('*'), $content);
    }
}
