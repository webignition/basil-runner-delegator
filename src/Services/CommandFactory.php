<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use Symfony\Component\Console\Command\Command;
use webignition\BasilCompiler\Compiler;
use webignition\BasilLoader\SourceLoader;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Command\RunCommand;
use webignition\BasilRunner\Services\GenerateCommand\ConfigurationFactory;
use webignition\BasilRunner\Services\GenerateCommand\ConfigurationValidator;
use webignition\BasilRunner\Services\GenerateCommand\ErrorOutputFactory;
use webignition\BasilRunner\Services\Generator\Renderer;
use webignition\BasilRunner\Services\RunCommand\ConsoleOutputFormatter;

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
        return new RunCommand($this->projectRootPath, new ConsoleOutputFormatter());
    }

    public function createGenerateCommand(): GenerateCommand
    {
        $externalVariableIdentifiers = ExternalVariableIdentifiersFactory::create();
        $configurationValidator = new ConfigurationValidator();

        return new GenerateCommand(
            SourceLoader::createLoader(),
            new TestGenerator(
                Compiler::create($externalVariableIdentifiers),
                new PhpFileCreator(),
            ),
            $this->projectRootPath,
            new ConfigurationFactory($this->projectRootPath),
            $configurationValidator,
            new ErrorOutputFactory($configurationValidator, new ValidatorInvalidResultSerializer()),
            new Renderer()
        );
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
