<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception;

class InvalidRemotePathException extends AbstractFailedTestExecutionException
{
    public function __construct(string $path)
    {
        parent::__construct($path, sprintf('Path "%s" not present on runner', $path));
    }
}
