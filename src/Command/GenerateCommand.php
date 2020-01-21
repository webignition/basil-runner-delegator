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
use webignition\BasilRunner\Model\GenerateCommand\OutputInterface as GenerateCommandOutputInterface;
use webignition\BasilRunner\Model\GenerateCommand\SuccessOutput;
use webignition\BasilRunner\Services\GenerateCommand\ConfigurationFactory;
use webignition\BasilRunner\Services\GenerateCommand\ConfigurationValidator;
use webignition\BasilRunner\Services\GenerateCommand\ErrorOutputFactory;
use webignition\BasilRunner\Services\Generator\Renderer;
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
    private $configurationFactory;
    private $configurationValidator;
    private $errorOutputFactory;
    private $outputRenderer;

    public function __construct(
        SourceLoader $sourceLoader,
        TestGenerator $testGenerator,
        ProjectRootPathProvider $projectRootPathProvider,
        ConfigurationFactory $configurationFactory,
        ConfigurationValidator $configurationValidator,
        ErrorOutputFactory $errorOutputFactory,
        Renderer $outputRenderer
    ) {
        parent::__construct();

        $this->sourceLoader = $sourceLoader;
        $this->testGenerator = $testGenerator;
        $this->projectRootPath = $projectRootPathProvider->get();
        $this->configurationFactory = $configurationFactory;
        $this->configurationValidator = $configurationValidator;
        $this->errorOutputFactory = $errorOutputFactory;
        $this->outputRenderer = $outputRenderer;
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputRenderer->setOutput($output);

        $typedInput = new TypedInput($input);

        $rawSource = trim((string) $typedInput->getStringOption(GenerateCommand::OPTION_SOURCE));
        $rawTarget = trim((string) $typedInput->getStringOption(GenerateCommand::OPTION_TARGET));
        $baseClass = trim((string) $typedInput->getStringOption(GenerateCommand::OPTION_BASE_CLASS));

        $configuration = $this->configurationFactory->create($rawSource, $rawTarget, $baseClass);

        if ('' === $rawSource) {
            return $this->render($this->errorOutputFactory->createForEmptySource($configuration));
        }

        if ('' === $rawTarget) {
            return $this->render($this->errorOutputFactory->createForEmptyTarget($configuration));
        }

        if (false === $this->configurationValidator->isValid($configuration)) {
            return $this->render($this->errorOutputFactory->createFromInvalidConfiguration($configuration));
        }

        $sourcePaths = $this->createSourcePaths($configuration->getSource());

        $generatedFiles = [];
        foreach ($sourcePaths as $sourcePath) {
            try {
                $testSuite = $this->sourceLoader->load($sourcePath);
            } catch (
                CircularStepImportException |
                EmptyTestException |
                InvalidPageException |
                InvalidTestException |
                NonRetrievableImportException |
                ParseException |
                UnknownElementException |
                UnknownItemException |
                UnknownPageElementException |
                UnknownTestException |
                YamlLoaderException $exception
            ) {
                $commandOutput = $this->errorOutputFactory->createForException($exception, $configuration);

                return $this->render($commandOutput);
            }

            try {
                foreach ($testSuite->getTests() as $test) {
                    $generatedFiles[] = $this->testGenerator->generate(
                        $test,
                        $configuration->getBaseClass(),
                        $configuration->getTarget()
                    );
                }
            } catch (
                UnresolvedPlaceholderException |
                UnsupportedStepException $exception
            ) {
                $commandOutput = $this->errorOutputFactory->createForException($exception, $configuration);

                return $this->render($commandOutput);
            }
        }

        $commandOutput = new SuccessOutput($configuration, $generatedFiles);

        return $this->render($commandOutput);
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

    private function render(GenerateCommandOutputInterface $commandOutput): int
    {
        $this->outputRenderer->render($commandOutput);

        return $commandOutput->getCode();
    }
}
