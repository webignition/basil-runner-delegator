<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\BasilRunner\Exception\MalformedSuiteManifestException;

class SuiteManifestFactory
{
    private Parser $yamlParser;

    public function __construct(Parser $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    public static function createFactory(): self
    {
        return new SuiteManifestFactory(
            new Parser()
        );
    }

    /**
     * @param string $content
     *
     * @return SuiteManifest
     *
     * @throws MalformedSuiteManifestException
     */
    public function createFromString(string $content): SuiteManifest
    {
        try {
            $data = $this->yamlParser->parse($content);
        } catch (ParseException $yamlParseException) {
            throw MalformedSuiteManifestException::createMalformedYamlException($content);
        }

        if (!is_array($data)) {
            throw MalformedSuiteManifestException::createNonArrayContentException($content);
        }

        return SuiteManifest::fromArray($data);
    }
}
