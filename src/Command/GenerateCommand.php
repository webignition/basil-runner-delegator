<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilCodeGenerator\UnresolvedPlaceholderException;
use webignition\BasilCompilableSourceFactory\Exception\UnsupportedStepException;
use webignition\BasilLoader\Exception\EmptyTestException;
use webignition\BasilLoader\Exception\InvalidPageException;
use webignition\BasilLoader\Exception\InvalidTestException;
use webignition\BasilLoader\Exception\NonRetrievableDataProviderException;
use webignition\BasilLoader\Exception\NonRetrievablePageException;
use webignition\BasilLoader\Exception\NonRetrievableStepException;
use webignition\BasilLoader\Exception\UnknownTestException;
use webignition\BasilLoader\Exception\YamlLoaderException;
use webignition\BasilLoader\SourceLoader;
use webignition\BasilModelProvider\Exception\UnknownDataProviderException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Exception\UnknownStepException;
use webignition\BasilParser\Exception\EmptyActionException;
use webignition\BasilParser\Exception\EmptyAssertionComparisonException;
use webignition\BasilParser\Exception\EmptyAssertionException;
use webignition\BasilParser\Exception\EmptyAssertionIdentifierException;
use webignition\BasilParser\Exception\EmptyAssertionValueException;
use webignition\BasilParser\Exception\EmptyInputActionValueException;
use webignition\BasilParser\Exception\InvalidActionIdentifierException;
use webignition\BasilResolver\CircularStepImportException;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;
use webignition\BasilRunner\Model\ErrorContext;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class GenerateCommand extends Command
{
    private const NAME = 'generate';

    private $sourceLoader;
    private $testGenerator;
    private $projectRootPath;
    private $generateCommandValidator;

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

    public function __construct(
        SourceLoader $sourceLoader,
        TestGenerator $testGenerator,
        ProjectRootPathProvider $projectRootPathProvider,
        GenerateCommandValidator $generateCommandValidator
    ) {
        parent::__construct();

        $this->sourceLoader = $sourceLoader;
        $this->testGenerator = $testGenerator;
        $this->projectRootPath = $projectRootPathProvider->get();
        $this->generateCommandValidator = $generateCommandValidator;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generate tests from basil source')
            ->addOption(
                'source',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the basil test source from which to generate tests. ' .
                'Can be absolute or relative to this directory.',
                ''
            )
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Output path for generated tests',
                ''
            )
            ->addOption(
                'base-class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Base class to extend',
                AbstractBaseTest::class
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws CircularStepImportException
     * @throws EmptyActionException
     * @throws EmptyAssertionComparisonException
     * @throws EmptyAssertionException
     * @throws EmptyAssertionIdentifierException
     * @throws EmptyAssertionValueException
     * @throws EmptyInputActionValueException
     * @throws EmptyTestException
     * @throws InvalidActionIdentifierException
     * @throws InvalidPageException
     * @throws InvalidTestException
     * @throws NonRetrievableDataProviderException
     * @throws NonRetrievablePageException
     * @throws NonRetrievableStepException
     * @throws UnknownDataProviderException
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownStepException
     * @throws UnknownTestException
     * @throws UnresolvedPlaceholderException
     * @throws UnsupportedStepException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typedInput = new TypedInput($input);

        $rawSource = (string) $typedInput->getStringOption('source');
        $source = $this->getAbsolutePath((string) $rawSource);

        $rawTarget = (string) $typedInput->getStringOption('target');
        $target = $this->getAbsolutePath($rawTarget);

        $fullyQualifiedBaseClass = (string) $typedInput->getStringOption('base-class');

        $validationResult = $this->generateCommandValidator->validate(
            $source,
            $rawSource,
            $target,
            $rawTarget,
            $fullyQualifiedBaseClass
        );

        if (false === $validationResult->getIsValid()) {
            $errorMessage = $this->errorMessages[$validationResult->getErrorCode()] ?? 'unknown';
            $errorOutput = new GenerateCommandErrorOutput(
                (string) $source,
                (string) $target,
                $fullyQualifiedBaseClass,
                $errorMessage,
                new ErrorContext(
                    ErrorContext::COMMAND_CONFIG,
                    ErrorContext::CODE_COMMAND_CONFIG,
                    $validationResult->getErrorCode()
                )
            );

            $output->writeln((string) json_encode($errorOutput, JSON_PRETTY_PRINT));

            return $validationResult->getErrorCode();
        }

        $source = (string) $source;
        $target = (string) $target;

        if (!class_exists($fullyQualifiedBaseClass)) {
            // Base class does not exist
            // Fail gracefully

            exit('Fix in #24');
        }

        $sourcePaths = $this->createSourcePaths($source);

        $generatedFiles = [];
        foreach ($sourcePaths as $sourcePath) {
            try {
                $testSuite = $this->sourceLoader->load($sourcePath);
            } catch (YamlLoaderException $yamlLoaderException) {
                $message = $yamlLoaderException->getMessage();
                $previousException = $yamlLoaderException->getPrevious();

                if ($previousException instanceof \Exception) {
                    $message = $previousException->getMessage();
                }

                $errorOutput = new GenerateCommandErrorOutput(
                    (string) $source,
                    (string) $target,
                    $fullyQualifiedBaseClass,
                    $message,
                    new ErrorContext(
                        ErrorContext::LOADER,
                        ErrorContext::CODE_LOADER,
                        GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION,
                        [
                            'path' => $yamlLoaderException->getPath()
                        ]
                    )
                );

                $output->writeln((string) json_encode($errorOutput, JSON_PRETTY_PRINT));

                return GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION;
            }

            foreach ($testSuite->getTests() as $test) {
                $generatedFiles[] = $this->testGenerator->generate($test, $fullyQualifiedBaseClass, $target);
            }
        }

        $commandOutput = new GenerateCommandSuccessOutput(
            $source,
            $target,
            $fullyQualifiedBaseClass,
            $generatedFiles
        );

        $output->writeln((string) json_encode($commandOutput, JSON_PRETTY_PRINT));

        return 0;
    }

    private function getAbsolutePath(string $path): ?string
    {
        if ('' === $path) {
            return null;
        }

        $isAbsolutePath = '/' === $path[0];
        if ($isAbsolutePath) {
            return $this->getRealPath($path);
        }

        return $this->getRealPath($this->projectRootPath . '/' . $path);
    }

    private function getRealPath(string $path): ?string
    {
        $path = realpath($path);

        return false === $path ? null : $path;
    }

    /**
     * @param string $source
     *
     * @return string[]
     */
    private function createSourcePaths(string $source): array
    {
        $sourcePaths = [];

        if (is_file($source)) {
            $sourcePaths[] = $source;
        }

        if (is_dir($source)) {
            return $this->findSourcePaths($source);
        }

        return $sourcePaths;
    }

    /**
     * @param string $directorySource
     *
     * @return string[]
     */
    private function findSourcePaths(string $directorySource): array
    {
        $sourcePaths = [];

        $directoryIterator = new \DirectoryIterator($directorySource);
        foreach ($directoryIterator as $item) {
            /* @var \DirectoryIterator $item */
            if ($item->isFile() && 'yml' === $item->getExtension()) {
                $sourcePaths[] = $item->getPath() . DIRECTORY_SEPARATOR . $item->getFilename();
            }
        }

        sort($sourcePaths);

        return $sourcePaths;
    }
}
