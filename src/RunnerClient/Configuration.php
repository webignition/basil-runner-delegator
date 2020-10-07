<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\RunnerClient;

class Configuration
{
    public const KEY_HOST = 'host';
    public const KEY_PORT = 'port';

    private string $host;
    private int $port;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param array<mixed> $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $host = $data[self::KEY_HOST] ?? '';
        if (!is_string($host)) {
            $host = '';
        }

        $port = $data[self::KEY_PORT] ?? 0;

        if (ctype_digit($port)) {
            $port = (int) $port;
        }

        if (!is_int($port)) {
            $port = 0;
        }

        return new Configuration($host, $port);
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
