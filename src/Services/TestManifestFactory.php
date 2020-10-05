<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Services;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilRunnerDelegator\Exception\MalformedManifestException;

class TestManifestFactory
{
    private Parser $yamlParser;

    public function __construct(Parser $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    public static function createFactory(): self
    {
        return new TestManifestFactory(
            new Parser()
        );
    }

    /**
     * @param string $content
     *
     * @return TestManifest
     *
     * @throws MalformedManifestException
     */
    public function createFromString(string $content): TestManifest
    {
        try {
            $data = $this->yamlParser->parse($content);
        } catch (ParseException $yamlParseException) {
            throw MalformedManifestException::createMalformedYamlException($content);
        }

        if (!is_array($data)) {
            throw MalformedManifestException::createNonArrayContentException($content);
        }

        return TestManifest::fromArray($data);
    }
}
