<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\TcpCliProxyClient\Client;

class RunnerClient extends Client
{
    private const RUNNER_COMMAND = './bin/runner --path=%s';

    public function request(string $request, ?callable $filter = null): void
    {
        parent::request(sprintf(
            self::RUNNER_COMMAND,
            $request
        ));
    }
}
