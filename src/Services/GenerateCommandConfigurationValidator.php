<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;

class GenerateCommandConfigurationValidator
{
    public function isValid(GenerateCommandConfiguration $configuration): bool
    {
        $source = $configuration->getSource();
        $target = $configuration->getTarget();

        if ('' === $source) {
            return false;
        }

        if (!is_readable($source)) {
            return false;
        }

        if ('' === $target) {
            return false;
        }

        if (!is_dir($target)) {
            return false;
        }

        if (!is_writable($target)) {
            return false;
        }

        if (!class_exists($configuration->getBaseClass())) {
            return false;
        }

        return true;
    }

    public function deriveInvalidConfigurationErrorCode(GenerateCommandConfiguration $configuration): int
    {
        $source = $configuration->getSource();
        if ('' === $source) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST;
        }

        if (!is_readable($source)) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE;
        }

        $target = $configuration->getTarget();
        if ('' === $target) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST;
        }

        if (!is_dir($target)) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY;
        }

        if (!is_writable($target)) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE;
        }

        if (!class_exists($configuration->getBaseClass())) {
            return GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST;
        }

        return 0;
    }
}
