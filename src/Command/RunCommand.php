<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilCompilerModels\InvalidSuiteManifestException;
use webignition\BasilRunner\Exception\MalformedSuiteManifestException;
use webignition\BasilRunner\Services\RunnerClient;
use webignition\BasilRunner\Services\SuiteManifestFactory;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class RunCommand extends Command
{
    public const OPTION_PATH = 'path';
    public const EXIT_CODE_PATH_NOT_A_FILE = 100;
    public const EXIT_CODE_PATH_NOT_READABLE = 101;
    public const EXIT_CODE_MANIFEST_FILE_READ_FAILED = 200;
    public const EXIT_CODE_MANIFEST_DATA_PARSE_FAILED = 300;
    public const EXIT_CODE_MANIFEST_INVALID = 400;

    private const NAME = 'run';

    /**
     * @var array<string, RunnerClient>
     */
    private array $runnerClients;
    private SuiteManifestFactory $suiteManifestFactory;

    /**
     * @param RunnerClient[] $runnerClients
     * @param SuiteManifestFactory $suiteManifestFactory
     */
    public function __construct(array $runnerClients, SuiteManifestFactory $suiteManifestFactory)
    {
        parent::__construct(self::NAME);

        $this->runnerClients = array_filter($runnerClients, function ($item) {
            return $item instanceof RunnerClient;
        });

        $this->suiteManifestFactory = $suiteManifestFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Command description')
            ->addOption(
                self::OPTION_PATH,
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to the suite manifest'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typedInput = new TypedInput($input);
        $path = (string) $typedInput->getStringOption(self::OPTION_PATH);

        if (!is_file($path)) {
            return self::EXIT_CODE_PATH_NOT_A_FILE;
        }

        if (!is_readable($path)) {
            return self::EXIT_CODE_PATH_NOT_READABLE;
        }

        $manifestContent = file_get_contents($path);
        if (false === $manifestContent) {
            return self::EXIT_CODE_MANIFEST_FILE_READ_FAILED;
        }

        try {
            $suiteManifest = $this->suiteManifestFactory->createFromString($manifestContent);
        } catch (InvalidSuiteManifestException $e) {
            // @todo: Log validation state in #522
            return self::EXIT_CODE_MANIFEST_INVALID;
        } catch (MalformedSuiteManifestException $e) {
            // @todo: Log yaml parsing exceptions in #521
            return self::EXIT_CODE_MANIFEST_DATA_PARSE_FAILED;
        }

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $testConfiguration = $testManifest->getConfiguration();

            $runnerClient = $this->runnerClients[$testConfiguration->getBrowser()] ?? null;

            if ($runnerClient instanceof RunnerClient) {
                $testPath = $testManifest->getTarget();
                // @todo: handle below exceptions in #537
                $runnerClient->request($testPath);
            } else {
                // @todo: handle in #531
            }
        }

        return 0;
    }
}
