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
use webignition\BasilCompiler\Compiler;
use webignition\BasilLoader\Exception\InvalidPageException;
use webignition\BasilLoader\Exception\InvalidTestException;
use webignition\BasilLoader\Exception\NonRetrievableDataProviderException;
use webignition\BasilLoader\Exception\NonRetrievablePageException;
use webignition\BasilLoader\Exception\NonRetrievableStepException;
use webignition\BasilLoader\Exception\YamlLoaderException;
use webignition\BasilLoader\TestLoader;
use webignition\BasilModelProvider\Exception\UnknownDataProviderException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Exception\UnknownStepException;
use webignition\BasilParser\Exception\EmptyActionException;
use webignition\BasilParser\Exception\EmptyAssertionComparisonException;
use webignition\BasilParser\Exception\EmptyAssertionException;
use webignition\BasilParser\Exception\EmptyAssertionIdentifierException;
use webignition\BasilParser\Exception\EmptyAssertionValueException;
use webignition\BasilParser\Exception\EmptyInputActionValueException;
use webignition\BasilResolver\CircularStepImportException;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Services\PhpFileCreator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class GenerateTestCommand extends Command
{
    private const NAME = 'generate-test';

    private $testLoader;
    private $compiler;
    private $phpFileCreator;
    private $projectRootPath;
    private $generateCommandValidator;

    private $errorMessages = [
        GenerateCommandErrorOutput::ERROR_CODE_SOURCE_EMPTY => 'source empty; call with --source=SOURCE',
        GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST => 'source invalid; does not exist',
        GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_A_FILE =>
            'source invalid; is not a file (is it a directory?)',
        GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_READABLE => 'source invalid; file is not readable',

        GenerateCommandErrorOutput::ERROR_CODE_TARGET_EMPTY => 'target empty; call with --target=TARGET',
        GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_DOES_NOT_EXIST => 'target invalid; does not exist',
        GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_A_DIRECTORY =>
            'target invalid; is not a directory (is it a file?)',
        GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_WRITABLE =>
            'target invalid; directory is not writable',
        GenerateCommandErrorOutput::ERROR_CODE_BASE_CLASS_DOES_NOT_EXIST => 'base class invalid: does not exist'
    ];

    public function __construct(
        TestLoader $testLoader,
        Compiler $compiler,
        PhpFileCreator $phpFileCreator,
        ProjectRootPathProvider $projectRootPathProvider,
        GenerateCommandValidator $generateCommandValidator
    ) {
        parent::__construct();

        $this->testLoader = $testLoader;
        $this->compiler = $compiler;
        $this->phpFileCreator = $phpFileCreator;
        $this->projectRootPath = $projectRootPathProvider->get();
        $this->generateCommandValidator = $generateCommandValidator;
    }

    protected function configure()
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
     * @throws UnresolvedPlaceholderException
     * @throws UnsupportedStepException
     * @throws YamlLoaderException
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
                $errorMessage
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

        $test = $this->testLoader->load($source);
        $className = $this->compiler->createClassName($test);
        $code = $this->compiler->compile($test, $fullyQualifiedBaseClass);

        $this->phpFileCreator->setOutputDirectory($target);
        $filename = $this->phpFileCreator->create($className, $code);

        $generatedFiles = [
            new GeneratedTestOutput($source, $filename),
        ];

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
}
