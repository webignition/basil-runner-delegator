<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\ConfigurationInterface;
use webignition\YamlDocumentGenerator\DocumentSourceInterface;

class Test implements DocumentSourceInterface
{
    private const TYPE = 'test';

    private string $path;
    private ConfigurationInterface $configuration;

    public function __construct(string $path, ConfigurationInterface $configuration)
    {
        $this->path = $path;
        $this->configuration = $configuration;
    }

    public static function fromTestManifest(TestManifest $testManifest): self
    {
        return new Test(
            $testManifest->getSource(),
            $testManifest->getConfiguration()
        );
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getData(): array
    {
        return [
            'path' => $this->path,
            'config' => [
                'browser' => $this->configuration->getBrowser(),
                'url' => $this->configuration->getUrl(),
            ],
        ];
    }
}
