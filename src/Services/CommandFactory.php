<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use Symfony\Component\Console\Command\Command;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Command\RunCommand;

class CommandFactory
{
    private string $projectRootPath;

    public function __construct(string $projectRootPath)
    {
        $this->projectRootPath = $projectRootPath;
    }

    public static function createFactory(): self
    {
        return new CommandFactory(
            (new ProjectRootPathProvider())->get()
        );
    }

    public function createRunCommand(): RunCommand
    {
        return new RunCommand($this->projectRootPath);
    }

    public function createGenerateCommand(): GenerateCommand
    {
        return new GenerateCommand();
    }

    /**
     * @return Command[]
     */
    public function createAll(): array
    {
        return [
            $this->createRunCommand(),
            $this->createGenerateCommand(),
        ];
    }
}
