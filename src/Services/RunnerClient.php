<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use webignition\BasilRunner\Model\RunnerClientConfiguration;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyModels\Output;

class RunnerClient extends Client
{
    private const RUNNER_COMMAND = './bin/runner --target=%s';

    public function __construct(RunnerClientConfiguration $configuration)
    {
        parent::__construct(
            $configuration->getHost(),
            $configuration->getPort()
        );
    }

    public function request(string $target): Output
    {
        return parent::request(sprintf(
            self::RUNNER_COMMAND,
            $target
        ));
    }
}
