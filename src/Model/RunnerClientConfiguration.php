<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class RunnerClientConfiguration
{
    private string $name;
    private string $host;
    private int $port;

    public function __construct(string $name, string $host, int $port)
    {
        $this->name = $name;
        $this->host = $host;
        $this->port = $port;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
