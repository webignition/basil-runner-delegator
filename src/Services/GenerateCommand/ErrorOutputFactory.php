<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\GenerateCommand;

use webignition\BasilLoader\Exception\EmptyTestException;
use webignition\BasilLoader\Exception\InvalidPageException;
use webignition\BasilLoader\Exception\YamlLoaderException;
use webignition\BasilResolver\CircularStepImportException;
use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Services\ValidatorInvalidResultSerializer;

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
    private $validatorInvalidResultSerializer;

    public function __construct(
        ConfigurationValidator $generateCommandConfigurationValidator,
        ValidatorInvalidResultSerializer $validatorInvalidResultSerializer
    ) {
        $this->generateCommandConfigurationValidator = $generateCommandConfigurationValidator;
        $this->validatorInvalidResultSerializer = $validatorInvalidResultSerializer;
    }

    public function createFromInvalidConfiguration(Configuration $configuration): ErrorOutput
    {
        return $this->createFromErrorCode(
            $configuration,
            $this->generateCommandConfigurationValidator->deriveInvalidConfigurationErrorCode($configuration)
        );
    }

    public function createForEmptySource(Configuration $configuration): ErrorOutput
    {
        return $this->createFromErrorCode($configuration, ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY);
    }

    public function createForEmptyTarget(Configuration $configuration): ErrorOutput
    {
        return $this->createFromErrorCode($configuration, ErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY);
    }

    public function createForYamlLoaderException(
        YamlLoaderException $yamlLoaderException,
        Configuration $configuration
    ): ErrorOutput {
        $message = $yamlLoaderException->getMessage();
        $previousException = $yamlLoaderException->getPrevious();

        if ($previousException instanceof \Exception) {
            $message = $previousException->getMessage();
        }

        return new ErrorOutput(
            $configuration,
            $message,
            ErrorOutput::CODE_LOADER_INVALID_YAML,
            [
                'path' => $yamlLoaderException->getPath()
            ]
        );
    }

    public function createForCircularStepImportException(
        CircularStepImportException $circularStepImportException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $circularStepImportException->getMessage(),
            ErrorOutput::CODE_LOADER_CIRCULAR_STEP_IMPORT,
            [
                'import_name' => $circularStepImportException->getImportName(),
            ]
        );
    }

    public function createForEmptyTestException(
        EmptyTestException $emptyTestException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $emptyTestException->getMessage(),
            ErrorOutput::CODE_LOADER_EMPTY_TEST,
            [
                'path' => $emptyTestException->getPath(),
            ]
        );
    }

    public function createForInvalidPageException(
        InvalidPageException $invalidPageException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $invalidPageException->getMessage(),
            ErrorOutput::CODE_LOADER_INVALID_PAGE,
            [
                'import_name' => $invalidPageException->getImportName(),
                'path' => $invalidPageException->getPath(),
                'validation-result' => $this->validatorInvalidResultSerializer->serializeToArray(
                    $invalidPageException->getValidationResult()
                )
            ]
        );
    }

    private function createFromErrorCode(Configuration $configuration, int $errorCode): ErrorOutput
    {
        $errorMessage = $this->errorMessages[$errorCode] ?? 'unknown';

        return new ErrorOutput(
            $configuration,
            $errorMessage,
            $errorCode
        );
    }
}
