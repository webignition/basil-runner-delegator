<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BasilCompiler\Compiler;
use webignition\BasilLoader\TestLoader;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Model\GeneratedTestOutput;
use webignition\BasilRunner\Services\PhpFileCreator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class GenerateCommand extends Command
{
    private const NAME = 'generate-test';

    private $testLoader;
    private $compiler;
    private $phpFileCreator;
    private $projectRootPath;
    private $generateCommandValidator;

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
     * @return int|null
     *
     * @throws \webignition\BasilCodeGenerator\UnresolvedPlaceholderException
     * @throws \webignition\BasilCompilableSourceFactory\Exception\UnknownObjectPropertyException
     * @throws \webignition\BasilCompilableSourceFactory\Exception\UnsupportedModelException
     * @throws \webignition\BasilLoader\Exception\NonRetrievableDataProviderException
     * @throws \webignition\BasilLoader\Exception\NonRetrievablePageException
     * @throws \webignition\BasilLoader\Exception\NonRetrievableStepException
     * @throws \webignition\BasilLoader\Exception\YamlLoaderException
     * @throws \webignition\BasilModelFactory\Exception\EmptyAssertionStringException
     * @throws \webignition\BasilModelFactory\Exception\InvalidActionTypeException
     * @throws \webignition\BasilModelFactory\Exception\InvalidIdentifierStringException
     * @throws \webignition\BasilModelFactory\Exception\MissingValueException
     * @throws \webignition\BasilModelFactory\InvalidPageElementIdentifierException
     * @throws \webignition\BasilModelFactory\MalformedPageElementReferenceException
     * @throws \webignition\BasilModelProvider\Exception\UnknownDataProviderException
     * @throws \webignition\BasilModelProvider\Exception\UnknownPageException
     * @throws \webignition\BasilModelProvider\Exception\UnknownStepException
     * @throws \webignition\BasilModelResolver\CircularStepImportException
     * @throws \webignition\BasilModelResolver\UnknownElementException
     * @throws \webignition\BasilModelResolver\UnknownPageElementException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typedInput = new TypedInput($input);

        $rawSource = (string) $typedInput->getStringOption('source');
        $source = $this->getAbsolutePath((string) $rawSource);

        $rawTarget = (string) $typedInput->getStringOption('target');
        $target = $this->getAbsolutePath($rawTarget);

        $sourceValidationResult = $this->generateCommandValidator->validateSource($source, $target, $rawSource);

        if (false === $sourceValidationResult->getIsValid()) {
            $output->writeln((string) json_encode($sourceValidationResult->getErrorOutput(), JSON_PRETTY_PRINT));

            return $sourceValidationResult->getExitCode();
        }

        $source = (string) $source;

        if (null === $target) {
            // Target does not exist
            // Check if target is a directory, is writable
            // Fail gracefully

            exit('Fix in #23');
        }

        $fullyQualifiedBaseClass = (string) $typedInput->getStringOption('base-class');

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

        $commandOutput = new GenerateCommandSuccessOutput($source, $target, $generatedFiles);

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
