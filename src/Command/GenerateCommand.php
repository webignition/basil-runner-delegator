<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BaseBasilTestCase\AbstractBaseTest;

class GenerateCommand extends Command
{
    public const OPTION_SOURCE = 'source';
    public const OPTION_TARGET = 'target';
    public const OPTION_BASE_CLASS = 'base-class';

    private const NAME = 'generate';

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
        $commandOptionsString = $this->createCommandOptionsString($input->getOptions());

        $compilerCommand = './compiler.phar ' . $commandOptionsString;
        $compilerCommandOutput = [];
        $compilerCommandExitCode = null;

        exec($compilerCommand, $compilerCommandOutput, $compilerCommandExitCode);

        $output->write(implode("\n", $compilerCommandOutput));

        return $compilerCommandExitCode;
    }

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
