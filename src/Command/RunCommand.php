<?php declare(strict_types=1);

namespace webignition\BasilRunner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    private const NAME = 'basil-runner:run';

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Command description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $execOutput = [];
        $returnCode = 0;
        $command = __DIR__ . '/../../vendor/bin/phpunit -c ' . __DIR__ . '/../../phpunit.run.xml --colors=always';

        exec($command, $execOutput, $returnCode);

        $output->writeln(implode("\n", $execOutput));

        return 0;
    }
}
