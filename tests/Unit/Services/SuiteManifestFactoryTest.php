<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilRunnerDelegator\Exception\MalformedSuiteManifestException;
use webignition\BasilRunnerDelegator\Services\SuiteManifestFactory;

class SuiteManifestFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateFromStringMalformedYamlException()
    {
        $content = 'malformed yaml fixture';
        $exception = new ParseException('yaml parse exception message');

        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parse')
            ->with($content)
            ->andThrow($exception);

        $factory = new SuiteManifestFactory($yamlParser);

        $this->expectExceptionObject(MalformedSuiteManifestException::createMalformedYamlException($content));

        $factory->createFromString($content);
    }

    public function testCreateFromStringThrowsNonArrayContentException()
    {
        $content = '';

        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parse')
            ->with($content)
            ->andReturn('');

        $factory = new SuiteManifestFactory($yamlParser);

        $this->expectExceptionObject(MalformedSuiteManifestException::createNonArrayContentException($content));

        $factory->createFromString($content);
    }

    public function testCreateFromStringSuccess()
    {
        $content = 'valid content';
        $data = [
            'config' => [
                'source' => '/source',
                'target' => '/target',
                'base-class' => 'BaseClass',
            ],
            'manifests' => [
                [
                    'config' => [
                        'browser' => 'chrome',
                        'url' => 'http://example.com',
                    ],
                    'source' => '/source/test.yml',
                    'target' => '/target/GeneratedTest.php',
                ],
            ],

        ];

        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parse')
            ->with($content)
            ->andReturn($data);

        $factory = new SuiteManifestFactory($yamlParser);

        self::assertEquals(SuiteManifest::fromArray($data), $factory->createFromString($content));
    }
}
