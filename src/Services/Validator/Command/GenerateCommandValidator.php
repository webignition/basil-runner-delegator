<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\Validator\Command;

use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;

class GenerateCommandValidator
{
    private const EXIT_CODE_SOURCE_EMPTY = 1;
    private const EXIT_CODE_SOURCE_INVALID_DOES_NOT_EXIST = 2;
    private const EXIT_CODE_SOURCE_INVALID_NOT_A_FILE = 3;
    private const EXIT_CODE_SOURCE_INVALID_NOT_READABLE = 4;

    public function validateSource(?string $source, ?string $target, string $rawSource)
    {
        if (null === $source) {
            if ('' === $rawSource) {
                return new GenerateCommandValidationResult(
                    false,
                    $this->createErrorOutput(
                        $source,
                        $target,
                        'source empty; call with --source=SOURCE'
                    ),
                    self::EXIT_CODE_SOURCE_EMPTY
                );
            }

            return new GenerateCommandValidationResult(
                false,
                $this->createErrorOutput(
                    $source,
                    $target,
                    'source invalid; does not exist'
                ),
                self::EXIT_CODE_SOURCE_INVALID_DOES_NOT_EXIST
            );
        }

        if (!is_file($source)) {
            return new GenerateCommandValidationResult(
                false,
                $this->createErrorOutput(
                    $source,
                    $target,
                    'source invalid; is not a file (is it a directory?)'
                ),
                self::EXIT_CODE_SOURCE_INVALID_NOT_A_FILE
            );
        }

        if (!is_readable($source)) {
            return new GenerateCommandValidationResult(
                false,
                $this->createErrorOutput(
                    $source,
                    $target,
                    'source invalid; file is not readable'
                ),
                self::EXIT_CODE_SOURCE_INVALID_NOT_READABLE
            );
        }

        return new GenerateCommandValidationResult(true);
    }

    private function createErrorOutput(
        ?string $source,
        ?string $target,
        string $errorMessage
    ): GenerateCommandErrorOutput {
        return new GenerateCommandErrorOutput((string) $source, (string) $target, $errorMessage);
    }
}
