<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\GenerateCommand;

use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Services\GenerateCommand\ConfigurationValidator;

class ErrorOutputFactory
{
    /**
     * @var array<int, string>
     */
    private $errorMessages = [
        ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY =>
            'source empty; call with --source=SOURCE',
        ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST =>
            'source invalid; does not exist',
        ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE =>
            'source invalid; file is not readable',
        ErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY =>
            'target empty; call with --target=TARGET',
        ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST =>
            'target invalid; does not exist',
        ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY =>
            'target invalid; is not a directory (is it a file?)',
        ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE =>
            'target invalid; directory is not writable',
        ErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST =>
            'base class invalid: does not exist'
    ];

    private $generateCommandConfigurationValidator;

    public function __construct(ConfigurationValidator $generateCommandConfigurationValidator)
    {
        $this->generateCommandConfigurationValidator = $generateCommandConfigurationValidator;
    }

    public function createFromInvalidConfiguration(Configuration $configuration): ErrorOutput
    {
        return $this->create(
            $configuration,
            $this->generateCommandConfigurationValidator->deriveInvalidConfigurationErrorCode($configuration)
        );
    }

    public function createForEmptySource(Configuration $configuration): ErrorOutput
    {
        return $this->create($configuration, ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY);
    }

    public function createForEmptyTarget(Configuration $configuration): ErrorOutput
    {
        return $this->create($configuration, ErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY);
    }

    private function create(Configuration $configuration, int $errorCode): ErrorOutput
    {
        $errorMessage = $this->errorMessages[$errorCode] ?? 'unknown';

        return new ErrorOutput(
            $configuration,
            $errorMessage,
            $errorCode
        );
    }
}
