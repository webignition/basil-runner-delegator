<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilRunner\Exception\MalformedSuiteManifestException;
use webignition\BasilRunner\Services\RunnerClient;
use webignition\BasilRunner\Services\SuiteManifestFactory;
use webignition\BasilRunner\Services\TestFactory;
use webignition\SymfonyConsole\TypedInput\TypedInput;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;
use webignition\YamlDocumentGenerator\YamlGenerator;

class RunCommand extends Command
{
    public const OPTION_PATH = 'path';
    public const EXIT_CODE_PATH_NOT_A_FILE = 100;
    public const EXIT_CODE_PATH_NOT_READABLE = 101;
    public const EXIT_CODE_MANIFEST_FILE_READ_FAILED = 120;
    public const EXIT_CODE_MANIFEST_DATA_PARSE_FAILED = 121;

    private const NAME = 'run';

    /**
     * @var array<string, RunnerClient>
     */
    private array $runnerClients;
    private SuiteManifestFactory $suiteManifestFactory;
    private LoggerInterface $logger;
    private YamlGenerator $yamlGenerator;
    private TestFactory $testFactory;

    /**
     * @param RunnerClient[] $runnerClients
     * @param SuiteManifestFactory $suiteManifestFactory
     * @param LoggerInterface $logger
     * @param YamlGenerator $yamlGenerator
     */
    public function __construct(
        array $runnerClients,
        SuiteManifestFactory $suiteManifestFactory,
        LoggerInterface $logger,
        YamlGenerator $yamlGenerator,
        TestFactory $testFactory
    ) {
        parent::__construct(self::NAME);

        $this->runnerClients = array_filter($runnerClients, function ($item) {
            return $item instanceof RunnerClient;
        });

        $this->suiteManifestFactory = $suiteManifestFactory;
        $this->logger = $logger;
        $this->yamlGenerator = $yamlGenerator;
        $this->testFactory = $testFactory;
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
        } catch (MalformedSuiteManifestException $e) {
            $this->logException($e, $path, [
                'content' => $e->getContent(),
            ]);

            return self::EXIT_CODE_MANIFEST_DATA_PARSE_FAILED;
        }

        foreach ($suiteManifest->getTestManifests() as $testManifest) {
            $output->write($this->yamlGenerator->generate(
                $this->testFactory->fromTestManifest($testManifest)
            ));

            $testConfiguration = $testManifest->getConfiguration();
            $browser = $testConfiguration->getBrowser();

            $runnerClient = $this->runnerClients[$browser] ?? null;

            if ($runnerClient instanceof RunnerClient) {
                $testPath = $testManifest->getTarget();

                try {
                    $runnerClient->request($testPath);
                    $output->writeln('');
                } catch (SocketErrorException $e) {
                    $this->logException($e, $path);
                } catch (ClientCreationException $e) {
                    $this->logException($e, $path, [
                        'connection-string' => $e->getConnectionString(),
                    ]);
                }
            } else {
                $this->logger->debug(
                    'Unknown browser \'' . $browser . '\'',
                    array_merge(['path' => $path], [
                        'browser' => $browser,
                        'manifest-data' => $testManifest->getData(),
                    ])
                );
            }
        }

        return 0;
    }

    /**
     * @param \Exception $exception
     * @param string $path
     * @param array<mixed> $context
     */
    private function logException(\Exception $exception, string $path, array $context = []): void
    {
        $this->logger->debug(
            $exception->getMessage(),
            array_merge(['path' => $path], $context)
        );
    }
}
