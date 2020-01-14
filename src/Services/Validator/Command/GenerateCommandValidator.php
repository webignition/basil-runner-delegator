<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\Validator\Command;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;

class GenerateCommandValidator
{
    public function validate(
        GenerateCommandConfiguration $configuration,
        string $rawSource,
        string $rawTarget
    ): GenerateCommandValidationResult {
        $sourceValidationErrorCode = $this->getSourceValidationErrorCode($configuration->getSource(), $rawSource);
        if (0 !== $sourceValidationErrorCode) {
            return new GenerateCommandValidationResult($configuration, false, $sourceValidationErrorCode);
        }

        $targetValidationErrorCode = $this->getTargetValidationErrorCode($configuration->getTarget(), $rawTarget);
        if (0 !== $targetValidationErrorCode) {
            return new GenerateCommandValidationResult($configuration, false, $targetValidationErrorCode);
        }

        if (!class_exists($configuration->getBaseClass())) {
            return new GenerateCommandValidationResult(
                $configuration,
                false,
                GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
            );
        }

        return new GenerateCommandValidationResult($configuration, true);
    }

    private function getSourceValidationErrorCode(string $source, string $rawSource): int
    {
        if ('' === $rawSource) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY;
        }

        if ('' === $source) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST;
        }

        if (!is_readable($source)) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE;
        }

        return 0;
    }

    private function getTargetValidationErrorCode(string $target, string $rawTarget): int
    {
        if ('' === $rawTarget) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY;
        }

        if ('' === $target) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST;
        }

        if (!is_dir($target)) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY;
        }

        if (!is_writable($target)) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE;
        }

        return 0;
    }
}
