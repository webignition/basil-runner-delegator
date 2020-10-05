<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilRunnerDelegator\Exception\MalformedManifestException;
use webignition\BasilRunnerDelegator\Services\TestManifestFactory;

class TestManifestFactoryTest extends TestCase
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

        $factory = new TestManifestFactory($yamlParser);

        $this->expectExceptionObject(MalformedManifestException::createMalformedYamlException($content));

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

        $factory = new TestManifestFactory($yamlParser);

        $this->expectExceptionObject(MalformedManifestException::createNonArrayContentException($content));

        $factory->createFromString($content);
    }

    public function testCreateFromStringSuccess()
    {
        $content = 'valid content';
        $data = [
            'config' => [
                'browser' => 'chrome',
                'url' => 'http://example.com',
            ],
            'source' => '/source/test.yml',
            'target' => '/target/GeneratedTest.php',
        ];

        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parse')
            ->with($content)
            ->andReturn($data);

        $factory = new TestManifestFactory($yamlParser);

        self::assertEquals(TestManifest::fromArray($data), $factory->createFromString($content));
    }
}
