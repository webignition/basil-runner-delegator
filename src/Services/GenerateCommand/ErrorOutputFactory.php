<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\GenerateCommand;

use webignition\BasilCompilableSourceFactory\Exception\UnsupportedContentException;
use webignition\BasilCompilableSourceFactory\Exception\UnsupportedStepException;
use webignition\BasilCompiler\UnresolvedPlaceholderException;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilLoader\Exception\EmptyTestException;
use webignition\BasilLoader\Exception\InvalidPageException;
use webignition\BasilLoader\Exception\InvalidTestException;
use webignition\BasilLoader\Exception\NonRetrievableImportException;
use webignition\BasilLoader\Exception\ParseException;
use webignition\BasilLoader\Exception\UnknownTestException;
use webignition\BasilLoader\Exception\YamlLoaderException;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilParser\Exception\UnparseableActionException;
use webignition\BasilParser\Exception\UnparseableAssertionException;
use webignition\BasilParser\Exception\UnparseableDataExceptionInterface;
use webignition\BasilParser\Exception\UnparseableStatementException;
use webignition\BasilParser\Exception\UnparseableStepException;
use webignition\BasilParser\Exception\UnparseableTestException;
use webignition\BasilResolver\CircularStepImportException;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;
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
    public const UNPARSEABLE_STEP_INVALID_ACTIONS_DATA = 'invalid-actions-data';
    public const UNPARSEABLE_STEP_INVALID_ASSERTIONS_DATA = 'invalid-assertions-data';
    public const REASON_UNKNOWN = 'unknown';

    /**
     * @var array<mixed>
     */
    private array $unparseableStatementErrorMessages = [
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
    private array $configurationErrorMessages = [
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

    private ConfigurationValidator $generateCommandConfigurationValidator;
    private ValidatorInvalidResultSerializer $validatorInvalidResultSerializer;

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

        if ($exception instanceof UnknownElementException && !$exception instanceof UnknownPageElementException) {
            return $this->createForUnknownElementException($exception, $configuration);
        }

        if ($exception instanceof UnknownItemException) {
            return $this->createForUnknownItemException($exception, $configuration);
        }

        if ($exception instanceof UnknownPageElementException) {
            return $this->createForUnknownPageElementException($exception, $configuration);
        }

        if ($exception instanceof UnknownTestException) {
            return $this->createForUnknownTestException($exception, $configuration);
        }

        if ($exception instanceof UnresolvedPlaceholderException) {
            return $this->createForUnresolvedPlaceholderException($exception, $configuration);
        }

        if ($exception instanceof UnsupportedStepException) {
            return $this->createForUnsupportedStepException($exception, $configuration);
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
                'test_path' => $invalidPageException->getTestPath(),
                'import_name' => $invalidPageException->getImportName(),
                'page_path' => $invalidPageException->getPath(),
                'validation_result' => $this->validatorInvalidResultSerializer->serializeToArray(
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
                'test_path' => $invalidTestException->getPath(),
                'validation_result' => $this->validatorInvalidResultSerializer->serializeToArray(
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
                'test_path' => $nonRetrievableImportException->getTestPath(),
                'type' => $nonRetrievableImportException->getType(),
                'name' => $nonRetrievableImportException->getName(),
                'import_path' => $nonRetrievableImportException->getPath(),
                'loader_error' => [
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
            $context['reason'] = $this->createInvalidStepStatementsDataReason($unparseableStepException->getCode());
        }

        if ($unparseableDataException instanceof UnparseableStepException) {
            $context['step_path'] = $parseException->getSubjectPath();
            $context['reason'] = $this->createInvalidStepStatementsDataReason($unparseableDataException->getCode());
        }

        if (
            $unparseableStatementException instanceof UnparseableActionException ||
            $unparseableStatementException instanceof UnparseableAssertionException
        ) {
            $statementType = $unparseableStatementException instanceof UnparseableActionException
                ? 'action'
                : 'assertion';

            $code = $unparseableStatementException->getCode();

            $context['statement_type'] = $statementType;
            $context['statement'] = $unparseableStatementException->getStatement();
            $context['reason'] =
                $this->unparseableStatementErrorMessages[$statementType][$code] ?? self::REASON_UNKNOWN;
        }

        return new ErrorOutput(
            $configuration,
            $unparseableDataException->getMessage(),
            ErrorOutput::CODE_LOADER_UNPARSEABLE_DATA,
            $context
        );
    }

    public function createForUnknownElementException(
        UnknownElementException $unknownElementException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $unknownElementException->getMessage(),
            ErrorOutput::CODE_LOADER_UNKNOWN_ELEMENT,
            array_merge(
                [
                    'element_name' => $unknownElementException->getElementName(),
                ],
                $this->createErrorOutputContextFromExceptionContext($unknownElementException->getExceptionContext())
            )
        );
    }

    public function createForUnknownItemException(
        UnknownItemException $unknownItemException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $unknownItemException->getMessage(),
            ErrorOutput::CODE_LOADER_UNKNOWN_ITEM,
            array_merge(
                [
                    'type' => $unknownItemException->getType(),
                    'name' => $unknownItemException->getName(),
                ],
                $this->createErrorOutputContextFromExceptionContext($unknownItemException->getExceptionContext())
            )
        );
    }

    public function createForUnknownPageElementException(
        UnknownPageElementException $unknownPageElementException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $unknownPageElementException->getMessage(),
            ErrorOutput::CODE_LOADER_UNKNOWN_PAGE_ELEMENT,
            array_merge(
                [
                    'import_name' => $unknownPageElementException->getImportName(),
                    'element_name' => $unknownPageElementException->getElementName(),
                ],
                $this->createErrorOutputContextFromExceptionContext($unknownPageElementException->getExceptionContext())
            )
        );
    }

    public function createForUnknownTestException(
        UnknownTestException $unknownTestException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $unknownTestException->getMessage(),
            ErrorOutput::CODE_LOADER_UNKNOWN_TEST,
            [
                'import_name' => $unknownTestException->getImportName(),
            ]
        );
    }

    public function createForUnresolvedPlaceholderException(
        UnresolvedPlaceholderException $unresolvedPlaceholderException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $unresolvedPlaceholderException->getMessage(),
            ErrorOutput::CODE_GENERATOR_UNRESOLVED_PLACEHOLDER,
            [
                'placeholder' => $unresolvedPlaceholderException->getPlaceholder(),
                'content' => $unresolvedPlaceholderException->getContent(),
            ]
        );
    }

    public function createForUnsupportedStepException(
        UnsupportedStepException $unsupportedStepException,
        Configuration $configuration
    ): ErrorOutput {
        return new ErrorOutput(
            $configuration,
            $unsupportedStepException->getMessage(),
            ErrorOutput::CODE_GENERATOR_UNSUPPORTED_STEP,
            $this->createErrorOutputContextFromUnsupportedStepException($unsupportedStepException)
        );
    }

    /**
     * @param UnsupportedStepException $unsupportedStepException
     *
     * @return array<string, string>
     */
    private function createErrorOutputContextFromUnsupportedStepException(
        UnsupportedStepException $unsupportedStepException
    ): array {
        $statementType = UnsupportedStepException::CODE_UNSUPPORTED_ACTION === $unsupportedStepException->getCode()
            ? 'action'
            : 'assertion';

        $unsupportedStatementException = $unsupportedStepException->getUnsupportedStatementException();

        $context = [
            'statement_type' => $statementType,
            'statement' => (string) $unsupportedStatementException->getStatement(),
        ];

        $unsupportedContentException = $unsupportedStatementException->getUnsupportedContentException();
        if ($unsupportedContentException instanceof UnsupportedContentException) {
            $context = array_merge($context, [
                'content_type' => $unsupportedContentException->getType(),
                'content' => (string) $unsupportedContentException->getContent(),
            ]);
        }

        return $context;
    }

    /**
     * @param ExceptionContextInterface $exceptionContext
     *
     * @return array<string, string>
     */
    private function createErrorOutputContextFromExceptionContext(ExceptionContextInterface $exceptionContext): array
    {
        return [
            'test_path' => (string) $exceptionContext->getTestName(),
            'step_name' => (string) $exceptionContext->getStepName(),
            'statement' => (string) $exceptionContext->getContent(),
        ];
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
        $errorMessage = $this->configurationErrorMessages[$errorCode] ?? self::REASON_UNKNOWN;

        return new ErrorOutput(
            $configuration,
            $errorMessage,
            $errorCode
        );
    }

    private function createUnknownErrorOutput(Configuration $configuration): ErrorOutput
    {
        return new ErrorOutput(
            $configuration,
            'An unknown error has occurred',
            ErrorOutput::CODE_UNKNOWN
        );
    }

    private function createInvalidStepStatementsDataReason(int $code): string
    {
        if (UnparseableStepException::CODE_INVALID_ACTIONS_DATA === $code) {
            return self::UNPARSEABLE_STEP_INVALID_ACTIONS_DATA;
        }

        if (UnparseableStepException::CODE_INVALID_ASSERTIONS_DATA === $code) {
            return self::UNPARSEABLE_STEP_INVALID_ASSERTIONS_DATA;
        }

        return self::REASON_UNKNOWN;
    }
}
