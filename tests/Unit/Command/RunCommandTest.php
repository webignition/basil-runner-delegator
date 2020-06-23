<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Command;

use phpmock\mockery\PHPMockery;
use Symfony\Component\Console\Tester\CommandTester;
use webignition\BasilRunner\Command\RunCommand;
use webignition\BasilRunner\Services\CommandFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class RunCommandTest extends AbstractBaseTest
{
    public function testRunUnableToStartBackgroundProcess()
    {
        $input = [
            '--path' => (new ProjectRootPathProvider())->get() . '/tests/build/target',
        ];

        $command = CommandFactory::createFactory()->createRunCommand();

        $commandTester = new CommandTester($command);

        PHPMockery::mock('webignition\BasilRunner\Command', 'popen')->andReturn(false);

        $exitCode = $commandTester->execute($input);
        $this->assertSame(RunCommand::RETURN_CODE_UNABLE_TO_OPEN_PROCESS, $exitCode);
    }
}
