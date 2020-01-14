<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

abstract class AbstractGenerateCommandOutput implements \JsonSerializable
{
    private $configuration;

    public function __construct(GenerateCommandConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): GenerateCommandConfiguration
    {
        return $this->configuration;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'config' => $this->configuration->jsonSerialize(),
        ];
    }
}
