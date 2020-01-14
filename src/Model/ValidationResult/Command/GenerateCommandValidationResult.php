<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ValidationResult\Command;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;

class GenerateCommandValidationResult
{
    private $configuration;
    private $errorCode;

    public function __construct(GenerateCommandConfiguration $configuration, int $errorCode)
    {
        $this->configuration = $configuration;
        $this->errorCode = $errorCode;
    }

    public function getConfiguration(): GenerateCommandConfiguration
    {
        return $this->configuration;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
