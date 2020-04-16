<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class KeyValueLine extends ActivityLine
{
    public function __construct(string $key, string $value)
    {
        parent::__construct(
            '-',
            sprintf(
                '%s: %s',
                $key,
                $value
            )
        );
    }
}
