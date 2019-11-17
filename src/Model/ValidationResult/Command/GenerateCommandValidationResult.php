<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ValidationResult\Command;

use webignition\BasilRunner\Model\GenerateCommandErrorOutput;

class GenerateCommandValidationResult
{
    private $isValid;
    private $errorOutput;
    private $exitCode;

    public function __construct(bool $isValid, ?GenerateCommandErrorOutput $errorOutput = null, ?int $exitcode = 0)
    {
        $errorOutput = $errorOutput ?? new GenerateCommandErrorOutput('', '', '');

        $this->isValid = $isValid;
        $this->errorOutput = $errorOutput;
        $this->exitCode = $exitcode;
    }

    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    public function getErrorOutput(): GenerateCommandErrorOutput
    {
        return $this->errorOutput;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }
}
