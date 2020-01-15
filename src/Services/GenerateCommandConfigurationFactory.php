<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilRunner\Model\GenerateCommand\Configuration;

class GenerateCommandConfigurationFactory
{
    private $projectRootPath;

    public function __construct(ProjectRootPathProvider $projectRootPathProvider)
    {
        $this->projectRootPath = $projectRootPathProvider->get();
    }

    public function create(string $rawSource, string $rawTarget, string $baseClass): Configuration
    {
        return new Configuration(
            (string) $this->getAbsolutePath($rawSource),
            (string) $this->getAbsolutePath($rawTarget),
            $baseClass
        );
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
