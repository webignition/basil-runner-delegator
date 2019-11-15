<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilCompiler\Compiler;
use webignition\BasilLoader\TestLoader;
use webignition\BasilLoader\TestSuiteLoader;

class GenerateCommand extends Command
{
    private const NAME = 'generate';

    private $testLoader;
    private $testSuiteLoader;
    private $compiler;

    public function __construct(
        TestLoader $testLoader,
        TestSuiteLoader $testSuiteLoader,
        Compiler $compiler
    ) {
        parent::__construct();

        $this->testLoader = $testLoader;
        $this->testSuiteLoader = $testSuiteLoader;
        $this->compiler = $compiler;
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generate tests from basil source')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Generate!');

        return 0;
    }
}
