<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ValidationResult\Command;

class GenerateCommandValidationResult
{
    private $isValid;
    private $errorCode;

    public function __construct(bool $isValid, int $errorCode = 0)
    {
        $this->isValid = $isValid;
        $this->errorCode = $errorCode;
    }

    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
