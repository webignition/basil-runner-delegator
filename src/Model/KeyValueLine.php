<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilRunner\Model\TerminalString\TerminalString;

class KeyValueLine extends ActivityLine
{
    public function __construct(string $key, string $value)
    {
        parent::__construct(
            new TerminalString('-'),
            new TerminalString(sprintf(
                '%s: %s',
                $key,
                $value
            ))
        );
    }
}
