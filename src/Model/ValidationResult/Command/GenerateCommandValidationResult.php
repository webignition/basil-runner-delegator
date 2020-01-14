<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ValidationResult\Command;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;

class GenerateCommandValidationResult
{
    private $configuration;
    private $isValid;
    private $errorCode;

    public function __construct(GenerateCommandConfiguration $configuration, bool $isValid, int $errorCode = 0)
    {
        $this->configuration = $configuration;
        $this->isValid = $isValid;
        $this->errorCode = $errorCode;
    }

    public function getConfiguration(): GenerateCommandConfiguration
    {
        return $this->configuration;
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
