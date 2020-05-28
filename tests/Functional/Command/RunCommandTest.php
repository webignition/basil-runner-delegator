<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilRunner\Command\RunCommand;
use webignition\BasilRunner\Tests\Functional\AbstractFunctionalTest;

class RunCommandTest extends AbstractFunctionalTest
{
    private RunCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = self::$container->get(RunCommand::class);
    }

    public function testRunFailurePathDoesNotExist()
    {
        $input = [
            '--path' => __DIR__ . '/non-existent',
        ];

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame(RunCommand::RETURN_CODE_INVALID_PATH, $exitCode);

        $commandOutputContent = $output->fetch();
        $this->assertEquals('', $commandOutputContent);
    }
}
