<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\RunnerClientConfiguration;

class RunnerClientConfigurationTest extends TestCase
{
    public function testCreate()
    {
        $name = 'chrome';
        $host = 'chrome-runner';
        $port = 9000;

        $configuration = new RunnerClientConfiguration($name, $host, $port);

        self::assertSame($name, $configuration->getName());
        self::assertSame($host, $configuration->getHost());
        self::assertSame($port, $configuration->getPort());
    }
}
