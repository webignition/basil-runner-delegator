<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\RunnerClient;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunnerDelegator\RunnerClient\Configuration;
use webignition\BasilRunnerDelegator\RunnerClient\ConfigurationFactory;

class ConfigurationFactoryTest extends TestCase
{
    /**
     * @dataProvider createCollectionFromEnvDataProvider
     *
     * @param array<mixed> $env
     * @param Configuration[] $expectedConfigurations
     */
    public function testCreateCollectionFromEnv(array $env, array $expectedConfigurations)
    {
        $factory = new ConfigurationFactory();

        $configurations = $factory->createCollectionFromEnv($env);

        self::assertEquals($expectedConfigurations, $configurations);
    }

    public function createCollectionFromEnvDataProvider(): array
    {
        return [
            'empty' => [
                'env' => [],
                'expectedConfigurations' => [],
            ],
            'single client, host only' => [
                'env' => [
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                ],
                'expectedConfigurations' => [
                    'chrome' => new Configuration('chrome-runner', 0),
                ],
            ],
            'single client, port only' => [
                'env' => [
                    'CHROME_RUNNER_PORT' => '9000',
                ],
                'expectedConfigurations' => [
                    'chrome' => new Configuration('', 9000),
                ],
            ],
            'single client, host then port' => [
                'env' => [
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                    'CHROME_RUNNER_PORT' => '9000',
                ],
                'expectedConfigurations' => [
                    'chrome' => new Configuration('chrome-runner', 9000),
                ],
            ],
            'single client, junk host then junk then port' => [
                'env' => [
                    1,
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                    true,
                    'CHROME_RUNNER_JUNK01' => 'red-herring-1',
                    'CHROME_RUNNER_PORT' => '9000',
                ],
                'expectedConfigurations' => [
                    'chrome' => new Configuration('chrome-runner', 9000),
                ],
            ],
            'single client, port then host' => [
                'env' => [
                    'CHROME_RUNNER_PORT' => '9000',
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                ],
                'expectedConfigurations' => [
                    'chrome' => new Configuration('chrome-runner', 9000),
                ],
            ],
            'two clients' => [
                'env' => [
                    'CHROME_RUNNER_HOST' => 'chrome-runner',
                    'CHROME_RUNNER_PORT' => '9000',
                    'FIREFOX_RUNNER_HOST' => 'firefox-runner',
                    'FIREFOX_RUNNER_PORT' => '9001',
                ],
                'expectedConfigurations' => [
                    'chrome' => new Configuration('chrome-runner', 9000),
                    'firefox' => new Configuration('firefox-runner', 9001),
                ],
            ],
        ];
    }
}
