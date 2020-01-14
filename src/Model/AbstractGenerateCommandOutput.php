<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

abstract class AbstractGenerateCommandOutput implements \JsonSerializable
{
    protected const STATUS_SUCCESS = 'success';
    protected const STATUS_FAILURE = 'failure';

    private $configuration;
    private $status;

    public function __construct(GenerateCommandConfiguration $configuration, string $status)
    {
        $this->configuration = $configuration;
        $this->status = $status;
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
            'status' => $this->status,
        ];
    }
}
