<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;

class GenerateCommandErrorOutputFactory
{
    /**
     * @var array<int, string>
     */
    private $errorMessages = [
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY =>
            'source empty; call with --source=SOURCE',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST =>
            'source invalid; does not exist',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE =>
            'source invalid; file is not readable',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY =>
            'target empty; call with --target=TARGET',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST =>
            'target invalid; does not exist',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY =>
            'target invalid; is not a directory (is it a file?)',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE =>
            'target invalid; directory is not writable',
        GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST =>
            'base class invalid: does not exist'
    ];

    private $generateCommandConfigurationValidator;

    public function __construct(GenerateCommandConfigurationValidator $generateCommandConfigurationValidator)
    {
        $this->generateCommandConfigurationValidator = $generateCommandConfigurationValidator;
    }

    public function createFromInvalidConfiguration(
        GenerateCommandConfiguration $configuration
    ): GenerateCommandErrorOutput {
        return $this->create(
            $configuration,
            $this->generateCommandConfigurationValidator->deriveInvalidConfigurationErrorCode($configuration)
        );
    }

    public function createForEmptySource(GenerateCommandConfiguration $configuration): GenerateCommandErrorOutput
    {
        return $this->create($configuration, GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY);
    }

    public function createForEmptyTarget(GenerateCommandConfiguration $configuration): GenerateCommandErrorOutput
    {
        return $this->create($configuration, GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY);
    }

    private function create(GenerateCommandConfiguration $configuration, int $errorCode): GenerateCommandErrorOutput
    {
        $errorMessage = $this->errorMessages[$errorCode] ?? 'unknown';

        return new GenerateCommandErrorOutput(
            $configuration,
            $errorMessage,
            $errorCode
        );
    }
}
