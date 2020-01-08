<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\Validator\Command;

use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;

class GenerateCommandValidator
{
    public function validate(
        ?string $source,
        string $rawSource,
        ?string $target,
        string $rawTarget,
        string $baseClass
    ): GenerateCommandValidationResult {
        $sourceValidationErrorCode = $this->getSourceValidationErrorCode($source, $rawSource);
        if (0 !== $sourceValidationErrorCode) {
            return new GenerateCommandValidationResult(false, $sourceValidationErrorCode);
        }

        $targetValidationErrorCode = $this->getTargetValidationErrorCode($target, $rawTarget);
        if (0 !== $targetValidationErrorCode) {
            return new GenerateCommandValidationResult(false, $targetValidationErrorCode);
        }

        if (!class_exists($baseClass)) {
            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_BASE_CLASS_DOES_NOT_EXIST
            );
        }

        return new GenerateCommandValidationResult(true);
    }

    private function getSourceValidationErrorCode(?string $source, string $rawSource): int
    {
        if (null === $source) {
            return '' === $rawSource
                ? GenerateCommandErrorOutput::ERROR_CODE_SOURCE_EMPTY
                : GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST;
        }

        if (!is_readable($source)) {
            return GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_READABLE;
        }

        return 0;
    }

    private function getTargetValidationErrorCode(?string $target, string $rawTarget): int
    {
        if (null === $target) {
            return '' === $rawTarget
                ? GenerateCommandErrorOutput::ERROR_CODE_TARGET_EMPTY
                : GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_DOES_NOT_EXIST;
        }

        if (!is_dir($target)) {
            return GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_A_DIRECTORY;
        }

        if (!is_writable($target)) {
            return GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_WRITABLE;
        }

        return 0;
    }
}
