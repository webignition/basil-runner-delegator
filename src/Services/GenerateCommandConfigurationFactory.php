<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Exception\GenerateCommandValidationException;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class GenerateCommandConfigurationFactory
{
    private $validator;
    private $projectRootPath;

    public function __construct(
        GenerateCommandValidator $validator,
        ProjectRootPathProvider $projectRootPathProvider
    ) {
        $this->validator = $validator;
        $this->projectRootPath = $projectRootPathProvider->get();
    }

    /**
     * @param TypedInput $input
     *
     * @return GenerateCommandConfiguration
     *
     * @throws GenerateCommandValidationException
     */
    public function createFromTypedInput(TypedInput $input): GenerateCommandConfiguration
    {
        $rawSource = (string) $input->getStringOption(GenerateCommand::OPTION_SOURCE);
        $source = $this->getAbsolutePath((string) $rawSource);

        $rawTarget = (string) $input->getStringOption(GenerateCommand::OPTION_TARGET);
        $target = $this->getAbsolutePath($rawTarget);

        $baseClass = (string) $input->getStringOption(GenerateCommand::OPTION_BASE_CLASS);

        $configuration = new GenerateCommandConfiguration(
            (string) $source,
            (string) $target,
            $baseClass
        );

        $validationResult = $this->validator->validate(
            $configuration,
            $rawSource,
            $rawTarget
        );

        if (false === $validationResult->getIsValid()) {
            throw new GenerateCommandValidationException($validationResult);
        }

        return $configuration;
    }

    private function getAbsolutePath(string $path): ?string
    {
        if ('' === $path) {
            return null;
        }

        $isAbsolutePath = '/' === $path[0];
        if ($isAbsolutePath) {
            return $this->getRealPath($path);
        }

        return $this->getRealPath($this->projectRootPath . '/' . $path);
    }

    private function getRealPath(string $path): ?string
    {
        $path = realpath($path);

        return false === $path ? null : $path;
    }
}
