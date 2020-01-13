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
use webignition\BasilLoader\Exception\NonRetrievableImportException;
use webignition\BasilLoader\Exception\ParseException;
use webignition\BasilLoader\Exception\UnknownTestException;
use webignition\BasilLoader\Exception\YamlLoaderException;
use webignition\BasilLoader\SourceLoader;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilResolver\CircularStepImportException;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;
use webignition\BasilRunner\Model\GenerateCommandSuccessOutput;
use webignition\BasilRunner\Services\GenerateCommandConfigurationFactory;
use webignition\BasilRunner\Services\GenerateCommandConfigurationValidator;
use webignition\BasilRunner\Services\GenerateCommandErrorOutputFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\TestGenerator;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class GenerateCommand extends Command
{
    public const OPTION_SOURCE = 'source';
    public const OPTION_TARGET = 'target';
    public const OPTION_BASE_CLASS = 'base-class';

    private const NAME = 'generate';

    private $sourceLoader;
    private $testGenerator;
    private $projectRootPath;
    private $generateCommandConfigurationFactory;
    private $generateCommandConfigurationValidator;
    private $generateCommandErrorOutputFactory;

    public function __construct(
        SourceLoader $sourceLoader,
        TestGenerator $testGenerator,
        ProjectRootPathProvider $projectRootPathProvider,
        GenerateCommandConfigurationFactory $generateCommandConfigurationFactory,
        GenerateCommandConfigurationValidator $generateCommandConfigurationValidator,
        GenerateCommandErrorOutputFactory $generateCommandErrorOutputFactory
    ) {
        parent::__construct();

        $this->sourceLoader = $sourceLoader;
        $this->testGenerator = $testGenerator;
        $this->projectRootPath = $projectRootPathProvider->get();
        $this->generateCommandConfigurationFactory = $generateCommandConfigurationFactory;
        $this->generateCommandConfigurationValidator = $generateCommandConfigurationValidator;
        $this->generateCommandErrorOutputFactory = $generateCommandErrorOutputFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generate tests from basil source')
            ->addOption(
                self::OPTION_SOURCE,
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the basil test source from which to generate tests. ' .
                'Can be absolute or relative to this directory.',
                ''
            )
            ->addOption(
                self::OPTION_TARGET,
                null,
                InputOption::VALUE_REQUIRED,
                'Output path for generated tests',
                ''
            )
            ->addOption(
                self::OPTION_BASE_CLASS,
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
     * @throws EmptyTestException
     * @throws InvalidPageException
     * @throws InvalidTestException
     * @throws NonRetrievableImportException
     * @throws ParseException
     * @throws UnknownElementException
     * @throws UnknownItemException
     * @throws UnknownPageElementException
     * @throws UnknownTestException
     * @throws UnresolvedPlaceholderException
     * @throws UnsupportedStepException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typedInput = new TypedInput($input);

        $rawSource = trim((string) $typedInput->getStringOption(GenerateCommand::OPTION_SOURCE));
        $rawTarget = trim((string) $typedInput->getStringOption(GenerateCommand::OPTION_TARGET));
        $baseClass = trim((string) $typedInput->getStringOption(GenerateCommand::OPTION_BASE_CLASS));

        $configuration = $this->generateCommandConfigurationFactory->create($rawSource, $rawTarget, $baseClass);

        if ('' === $rawSource) {
            $errorOutput = $this->generateCommandErrorOutputFactory->createForEmptySource($configuration);

            $output->writeln((string) json_encode($errorOutput, JSON_PRETTY_PRINT));

            return $errorOutput->getErrorCode();
        }

        if ('' === $rawTarget) {
            $errorOutput = $this->generateCommandErrorOutputFactory->createForEmptyTarget($configuration);

            $output->writeln((string) json_encode($errorOutput, JSON_PRETTY_PRINT));

            return $errorOutput->getErrorCode();
        }

        if (false === $this->generateCommandConfigurationValidator->isValid($configuration)) {
            $errorOutput = $this->generateCommandErrorOutputFactory->createFromInvalidConfiguration($configuration);

            $output->writeln((string) json_encode($errorOutput, JSON_PRETTY_PRINT));

            return $errorOutput->getErrorCode();
        }

        $sourcePaths = $this->createSourcePaths($configuration->getSource());

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
            } catch (CircularStepImportException $circularStepImportException) {
                $errorOutput = new GenerateCommandErrorOutput(
                    (string) $source,
                    (string) $target,
                    $fullyQualifiedBaseClass,
                    $circularStepImportException->getMessage(),
                    new ErrorContext(
                        ErrorContext::RESOLVER,
                        ErrorContext::CODE_RESOLVER,
                        GenerateCommandErrorOutput::CODE_RESOLVER_EXCEPTION,
                        [
                            'import_name' => $circularStepImportException->getImportName(),
                        ]
                    )
                );

                $output->writeln((string) json_encode($errorOutput, JSON_PRETTY_PRINT));

                return GenerateCommandErrorOutput::CODE_LOADER_EXCEPTION;
            }

            foreach ($testSuite->getTests() as $test) {
                $generatedFiles[] = $this->testGenerator->generate(
                    $test,
                    $configuration->getBaseClass(),
                    $configuration->getTarget()
                );
            }
        }

        $commandOutput = new GenerateCommandSuccessOutput($configuration, $generatedFiles);

        $output->writeln((string) json_encode($commandOutput, JSON_PRETTY_PRINT));

        return 0;
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
