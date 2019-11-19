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
        string $rawTarget
    ): GenerateCommandValidationResult {
        $sourceValidationResult = $this->validateSource($source, $rawSource);
        if (false === $sourceValidationResult->getIsValid()) {
            return $sourceValidationResult;
        }

        $targetValidationResult = $this->validateTarget($target, $rawTarget);
        if (false === $targetValidationResult->getIsValid()) {
            return $targetValidationResult;
        }

        return new GenerateCommandValidationResult(true);
    }

    private function validateSource(?string $source, string $rawSource): GenerateCommandValidationResult
    {
        if (null === $source) {
            if ('' === $rawSource) {
                return new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_EMPTY
                );
            }

            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST
            );
        }

        if (!is_file($source)) {
            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_A_FILE
            );
        }

        if (!is_readable($source)) {
            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_READABLE
            );
        }

        return new GenerateCommandValidationResult(true);
    }

    private function validateTarget(?string $target, string $rawTarget): GenerateCommandValidationResult
    {
        if (null === $target) {
            if ('' === $rawTarget) {
                return new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_TARGET_EMPTY
                );
            }

            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_DOES_NOT_EXIST
            );
        }

        if (!is_dir($target)) {
            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_A_DIRECTORY
            );
        }

        if (!is_writable($target)) {
            return new GenerateCommandValidationResult(
                false,
                GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_WRITABLE
            );
        }

        return new GenerateCommandValidationResult(true);
    }
}
