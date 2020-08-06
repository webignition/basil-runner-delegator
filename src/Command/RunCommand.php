<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use webignition\BasilPhpUnitResultPrinter\ResultPrinter;

class RunCommand extends Command
{
    public const OPTION_PATH = 'path';

    private const NAME = 'run';

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Command description')
            ->addOption(
                self::OPTION_PATH,
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to the directory of tests to run.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandOptionsString = $this->createCommandOptionsString($input->getOptions());

        $runnerCommand =
            './runner.phar ' .
            $commandOptionsString .
            ' --printer="' . ResultPrinter::class . '"';

        $process = Process::fromShellCommandline($runnerCommand);
        try {
            $process->mustRun(function ($type, $buffer) use ($output) {
                if (Process::OUT === $type) {
                    $output->write($buffer);
                }
            });
        } catch (ProcessFailedException $processFailedException) {
        }

        return (int) $process->getExitCode();
    }

    /**
     * @param array<mixed> $options
     *
     * @return string
     */
    private function createCommandOptionsString(array $options): string
    {
        $fooOptions = [];

        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $fooOptions[] = '--' . $key . '=' . escapeshellarg($value);
            }
        }

        return implode(' ', $fooOptions);
    }
}
