<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\GenerateCommand;

use webignition\BasilLoader\Exception\EmptyTestException;
use webignition\BasilLoader\Exception\InvalidPageException;
use webignition\BasilLoader\Exception\InvalidTestException;
use webignition\BasilLoader\Exception\NonRetrievableImportException;
use webignition\BasilLoader\Exception\ParseException;
use webignition\BasilLoader\Exception\YamlLoaderException;
use webignition\BasilParser\Exception\UnparseableActionException;
use webignition\BasilParser\Exception\UnparseableAssertionException;
use webignition\BasilParser\Exception\UnparseableDataExceptionInterface;
use webignition\BasilParser\Exception\UnparseableStatementException;
use webignition\BasilParser\Exception\UnparseableStepException;
use webignition\BasilParser\Exception\UnparseableTestException;
use webignition\BasilResolver\CircularStepImportException;
use webignition\BasilRunner\Model\GenerateCommand\Configuration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Services\ValidatorInvalidResultSerializer;

class ErrorOutputFactory
{
    public const UNPARSEABLE_ACTION_EMPTY = 'empty';
    public const UNPARSEABLE_ACTION_EMPTY_VALUE = 'empty-value';
    public const UNPARSEABLE_ACTION_INVALID_IDENTIFIER = 'invalid-identifier';
    public const UNPARSEABLE_ASSERTION_EMPTY = 'empty';
    public const UNPARSEABLE_ASSERTION_EMPTY_COMPARISON = 'empty-comparison';
    public const UNPARSEABLE_ASSERTION_EMPTY_IDENTIFIER = 'empty-identifier';
    public const UNPARSEABLE_ASSERTION_EMPTY_VALUE = 'empty-value';

    private $unparseableStatementErrorMessages = [
        'action' => [
            UnparseableActionException::CODE_EMPTY => self::UNPARSEABLE_ACTION_EMPTY,
            UnparseableActionException::CODE_EMPTY_INPUT_ACTION_VALUE => self::UNPARSEABLE_ACTION_EMPTY_VALUE,
            UnparseableActionException::CODE_INVALID_IDENTIFIER => self::UNPARSEABLE_ACTION_INVALID_IDENTIFIER,
        ],
        'assertion' => [
            UnparseableAssertionException::CODE_EMPTY => self::UNPARSEABLE_ASSERTION_EMPTY,
            UnparseableAssertionException::CODE_EMPTY_COMPARISON => self::UNPARSEABLE_ASSERTION_EMPTY_COMPARISON,
            UnparseableAssertionException::CODE_EMPTY_IDENTIFIER => self::UNPARSEABLE_ASSERTION_EMPTY_IDENTIFIER,
            UnparseableAssertionException::CODE_EMPTY_VALUE => self::UNPARSEABLE_ASSERTION_EMPTY_VALUE,
        ],
    ];

    /**
     * @var array<int, string>
     */
    private $configurationErrorMessages = [
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
        return $this->createForConfigurationErrorCode(
            $configuration,
            $this->generateCommandConfigurationValidator->deriveInvalidConfigurationErrorCode($configuration)
        );
    }

    public function createForEmptySource(Configuration $configuration): ErrorOutput
    {
        return $this->createForConfigurationErrorCode($configuration, ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY);
    }

    public function createForEmptyTarget(Configuration $configuration): ErrorOutput
    {
        return $this->createForConfigurationErrorCode($configuration, ErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY);
    }

    public function createForException(\Exception $exception, Configuration $configuration): ErrorOutput
    {
        if ($exception instanceof YamlLoaderException) {
            return $this->createForYamlLoaderException($exception, $configuration);
        }

        if ($exception instanceof CircularStepImportException) {
            return $this->createForCircularStepImportException($exception, $configuration);
        }

        if ($exception instanceof EmptyTestException) {
            return $this->createForEmptyTestException($exception, $configuration);
        }

        if ($exception instanceof InvalidPageException) {
            return $this->createForInvalidPageException($exception, $configuration);
        }

        if ($exception instanceof InvalidTestException) {
            return $this->createForInvalidTestException($exception, $configuration);
        }

        if ($exception instanceof NonRetrievableImportException) {
            return $this->createForNonRetrievableImportException($exception, $configuration);
        }

        if ($exception instanceof ParseException) {
            return $this->createForParseException($exception, $configuration);
        }

        return $this->createUnknownErrorOutput($configuration);
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
                'test' => $invalidPageException->getTestPath(),
                'import_name' => $invalidPageException->getImportName(),
                'path' => $invalidPageException->getPath(),
                'validation-result' => $this->validatorInvalidResultSerializer->serializeToArray(
                    $invalidPageException->getValidationResult()
                )
            ]
        );
    }

    public function createForInvalidTestException(
        InvalidTestException $invalidTestException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $invalidTestException->getMessage(),
            ErrorOutput::CODE_LOADER_INVALID_TEST,
            [
                'path' => $invalidTestException->getPath(),
                'validation-result' => $this->validatorInvalidResultSerializer->serializeToArray(
                    $invalidTestException->getValidationResult()
                )
            ]
        );
    }

    public function createForNonRetrievableImportException(
        NonRetrievableImportException $nonRetrievableImportException,
        Configuration $configuration
    ): ErrorOutput {
        $yamlLoaderException = $nonRetrievableImportException->getYamlLoaderException();

        $loaderMessage = $yamlLoaderException->getMessage();
        $loaderPreviousException = $yamlLoaderException->getPrevious();

        if ($loaderPreviousException instanceof \Exception) {
            $loaderMessage = $loaderPreviousException->getMessage();
        }

        return new ErrorOutput(
            $configuration,
            $nonRetrievableImportException->getMessage(),
            ErrorOutput::CODE_LOADER_NON_RETRIEVABLE_IMPORT,
            [
                'test' => $nonRetrievableImportException->getTestPath(),
                'type' => $nonRetrievableImportException->getType(),
                'name' => $nonRetrievableImportException->getName(),
                'path' => $nonRetrievableImportException->getPath(),
                'loader-error' => [
                    'message' => $loaderMessage,
                    'path' => $yamlLoaderException->getPath(),
                ]
            ]
        );
    }

    public function createForParseException(
        ParseException $parseException,
        Configuration $configuration
    ): ErrorOutput {
        $unparseableDataException = $parseException->getUnparseableDataException();
        $unparseableStatementException = $this->findUnparseableStatementException($unparseableDataException);

        $context = [
            'type' => $unparseableDataException instanceof UnparseableTestException ? 'test' : 'step',
            'test_path' => $parseException->getTestPath(),
        ];

        if ($unparseableDataException instanceof UnparseableTestException) {
            $unparseableStepException = $unparseableDataException->getUnparseableStepException();

            $context['step_name'] = $unparseableStepException->getStepName();
        }

        if ($unparseableDataException instanceof UnparseableStepException) {
            $context['step_path'] = $parseException->getSubjectPath();
        }

        if (
            $unparseableStatementException instanceof UnparseableActionException ||
            $unparseableStatementException instanceof UnparseableAssertionException
        ) {
            $statementType = $unparseableStatementException instanceof UnparseableActionException
                ? 'action'
                : 'assertion';

            $code = $unparseableStatementException->getCode();

            $context['statement-type'] = $statementType;
            $context['statement'] = $unparseableStatementException->getStatement();
            $context['reason'] = $this->unparseableStatementErrorMessages[$statementType][$code] ?? 'unknown';
        }

        return new ErrorOutput(
            $configuration,
            $unparseableDataException->getMessage(),
            ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
            $context
        );
    }

    private function findUnparseableStatementException(
        UnparseableDataExceptionInterface $unparseableDataException
    ): ?UnparseableStatementException {
        $unparseableStatementException = null;

        if ($unparseableDataException instanceof UnparseableStepException) {
            $unparseableStatementException = $unparseableDataException->getUnparseableStatementException();
        } elseif ($unparseableDataException instanceof UnparseableTestException) {
            $unparseableStepException = $unparseableDataException->getUnparseableStepException();
            $unparseableStatementException = $unparseableStepException->getUnparseableStatementException();
        }

        return $unparseableStatementException;
    }

    private function createForConfigurationErrorCode(Configuration $configuration, int $errorCode): ErrorOutput
    {
        $errorMessage = $this->configurationErrorMessages[$errorCode] ?? 'unknown';

        return new ErrorOutput(
            $configuration,
            $errorMessage,
            $errorCode
        );
    }

    private function createUnknownErrorOutput(Configuration $configuration)
    {
        return new ErrorOutput(
            $configuration,
            'An unknown error has occurred',
            ErrorOutput::CODE_UNKNOWN
        );
    }
}
