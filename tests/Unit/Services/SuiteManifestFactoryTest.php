<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilCompilerModels\InvalidSuiteManifestException;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilCompilerModels\SuiteManifestFactory as BaseSuiteManifestFactory;
use webignition\BasilRunner\Exception\MalformedSuiteManifestException;
use webignition\BasilRunner\Services\SuiteManifestFactory;

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

        $factory = new SuiteManifestFactory(
            $yamlParser,
            \Mockery::mock(BaseSuiteManifestFactory::class)
        );

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

        $factory = new SuiteManifestFactory(
            $yamlParser,
            \Mockery::mock(BaseSuiteManifestFactory::class)
        );

        $this->expectExceptionObject(MalformedSuiteManifestException::createNonArrayContentException($content));

        $factory->createFromString($content);
    }

    public function testCreateFromStringThrowsInvalidSuiteManifestException()
    {
        $exception = \Mockery::mock(InvalidSuiteManifestException::class);
        $baseFactory = \Mockery::mock(BaseSuiteManifestFactory::class);
        $baseFactory
            ->shouldReceive('createFromArray')
            ->with([])
            ->andThrow($exception);

        $factory = new SuiteManifestFactory(new Parser(), $baseFactory);

        $this->expectException(InvalidSuiteManifestException::class);

        $factory->createFromString('[]');
    }

    public function testCreateFromStringSuccess()
    {
        $content = 'valid content';
        $data = [
            'valid data',
        ];
        $suiteManifest = \Mockery::mock(SuiteManifest::class);

        $yamlParser = \Mockery::mock(Parser::class);
        $yamlParser
            ->shouldReceive('parse')
            ->with($content)
            ->andReturn($data);

        $baseFactory = \Mockery::mock(BaseSuiteManifestFactory::class);
        $baseFactory
            ->shouldReceive('createFromArray')
            ->with($data)
            ->andReturn($suiteManifest);

        $factory = new SuiteManifestFactory($yamlParser, $baseFactory);

        self::assertSame($suiteManifest, $factory->createFromString($content));
    }
}
