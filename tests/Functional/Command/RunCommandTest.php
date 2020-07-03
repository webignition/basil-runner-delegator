<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilRunner\Command\RunCommand;

class RunCommandTest extends \PHPUnit\Framework\TestCase
{
    private RunCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new RunCommand(__DIR__ . '/../..');
    }

    public function testRunFailurePathDoesNotExist()
    {
        $input = [
            '--path' => __DIR__ . '/non-existent',
        ];

        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        self::assertSame(100, $exitCode);

        $commandOutputContent = $output->fetch();
        self::assertEquals('', $commandOutputContent);
    }
}
