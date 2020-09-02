<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Services;

use webignition\BasilRunnerDelegator\Exception\InvalidRemotePathException;
use webignition\BasilRunnerDelegator\Exception\NonExecutableRemoteTestException;
use webignition\TcpCliProxyClient\Client;

class RunnerClient extends Client
{
    private const RUNNER_COMMAND = './bin/runner --path=%s';

    /**
     * @param string $request
     * @param callable|null $filter
     * @throws \webignition\TcpCliProxyClient\Exception\ClientCreationException
     * @throws \webignition\TcpCliProxyClient\Exception\SocketErrorException
     * @throws InvalidRemotePathException
     * @throws NonExecutableRemoteTestException
     */
    public function request(string $request, ?callable $filter = null): void
    {
        parent::request(
            sprintf(self::RUNNER_COMMAND, $request),
            function (string $line) use ($request) {
                if (ctype_digit($line)) {
                    $exitCode = (int) $line;

                    if (0 !== $exitCode) {
                        if (100 === $exitCode) {
                            throw new InvalidRemotePathException($request);
                        }

                        throw new NonExecutableRemoteTestException($request);
                    }

                    return null;
                } else {
                    return $line;
                }
            }
        );
    }
}
