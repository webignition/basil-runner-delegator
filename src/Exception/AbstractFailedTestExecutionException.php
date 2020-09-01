<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception;

abstract class AbstractFailedTestExecutionException extends \Exception
{
    private string $path;

    public function __construct(string $path, string $message, int $code = 0)
    {
        parent::__construct($message, $code);

        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
