<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\Validator\Command;

use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;

class GenerateCommandValidator
{
    public function validateSource(?string $source, string $rawSource)
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

    public function validateTarget(?string $target, string $rawTarget)
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
