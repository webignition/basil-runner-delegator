<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

abstract class AbstractGenerateCommandOutput implements GenerateCommandOutputInterface
{
    protected const STATUS_SUCCESS = 'success';
    protected const STATUS_FAILURE = 'failure';

    private $configuration;
    private $status;
    private $code;

    public function __construct(GenerateCommandConfiguration $configuration, string $status, int $code)
    {
        $this->configuration = $configuration;
        $this->status = $status;
        $this->code = $code;
    }

    public function getConfiguration(): GenerateCommandConfiguration
    {
        return $this->configuration;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): array
    {
        return $this->jsonSerialize();
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
