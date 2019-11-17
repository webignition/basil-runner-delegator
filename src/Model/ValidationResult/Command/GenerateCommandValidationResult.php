<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ValidationResult\Command;

use webignition\BasilRunner\Model\GenerateCommandErrorOutput;

class GenerateCommandValidationResult
{
    private $isValid;
    private $errorOutput;
    private $exitCode;

    public function __construct(bool $isValid, ?GenerateCommandErrorOutput $errorOutput = null, int $exitCode = 0)
    {
        $this->isValid = $isValid;
        $this->exitCode = $exitCode;

        if ($errorOutput instanceof GenerateCommandErrorOutput) {
            $this->errorOutput = $errorOutput;
        } else {
            $this->errorOutput = new GenerateCommandErrorOutput('', '', '');
        }
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
