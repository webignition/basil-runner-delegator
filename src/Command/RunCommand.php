<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilRunnerDelegator\Exception\InvalidRemotePathException;
use webignition\BasilRunnerDelegator\Exception\NonExecutableRemoteTestException;
use webignition\BasilRunnerDelegator\Services\RunnerClient;
use webignition\BasilRunnerDocuments\Exception;
use webignition\SymfonyConsole\TypedInput\TypedInput;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;
use webignition\YamlDocumentGenerator\YamlGenerator;

class RunCommand extends Command
{
    public const OPTION_BROWSER = 'browser';
    public const ARGUMENT_PATH = 'path';

    private const NAME = 'run';

    /**
     * @var array<string, RunnerClient>
     */
    private array $runnerClients;
    private LoggerInterface $logger;
    private YamlGenerator $yamlGenerator;

    /**
     * @param RunnerClient[] $runnerClients
     * @param LoggerInterface $logger
     * @param YamlGenerator $yamlGenerator
     */
    public function __construct(
        array $runnerClients,
        LoggerInterface $logger,
        YamlGenerator $yamlGenerator
    ) {
        parent::__construct(self::NAME);

        $this->runnerClients = array_filter($runnerClients, function ($item) {
            return $item instanceof RunnerClient;
        });

        $this->logger = $logger;
        $this->yamlGenerator = $yamlGenerator;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Command description')
            ->addOption(
                self::OPTION_BROWSER,
                null,
                InputOption::VALUE_REQUIRED,
                'Browser to use'
            )
            ->addArgument(
                self::ARGUMENT_PATH,
                InputArgument::REQUIRED,
                'Path to the generated test (to be passed on to a runner)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typedInput = new TypedInput($input);

        $browser = (string) $typedInput->getStringOption(self::OPTION_BROWSER);
        $path = (string) $typedInput->getStringArgument(self::ARGUMENT_PATH);

        $runnerClient = $this->runnerClients[$browser] ?? null;

        if ($runnerClient instanceof RunnerClient) {
            try {
                $runnerClient->request($path);
            } catch (SocketErrorException $e) {
                $this->logException($e);
            } catch (ClientCreationException $e) {
                $this->logException($e, [
                    'connection-string' => $e->getConnectionString(),
                ]);
            } catch (InvalidRemotePathException | NonExecutableRemoteTestException $remoteTestExecutionException) {
                $this->logException($remoteTestExecutionException, [
                    'remote-path' => $remoteTestExecutionException->getPath(),
                ]);

                $exception = Exception::createFromThrowable($remoteTestExecutionException)->withoutTrace();
                $output->write($this->yamlGenerator->generate($exception));
            }
        } else {
            $this->logger->debug(
                'Unknown browser \'' . $browser . '\'',
                [
                    'browser' => $browser,
                ]
            );
        }

        return Command::SUCCESS;
    }

    /**
     * @param \Exception $exception
     * @param array<mixed> $context
     */
    private function logException(\Exception $exception, array $context = []): void
    {
        $this->logger->debug(
            $exception->getMessage(),
            $context
        );
    }
}
