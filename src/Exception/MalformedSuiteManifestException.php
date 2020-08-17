<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Exception;

class MalformedSuiteManifestException extends \Exception
{
    private const CODE_MALFORMED_YAML = 0;
    private const CODE_NOT_AN_ARRAY = 1;
    private const MESSAGE_MALFORMED_YAML = 'Content is not parsable yaml';
    private const MESSAGE_EMPTY = 'Content is not a yaml array';

    public static function createNonArrayContentException(): self
    {
        return new MalformedSuiteManifestException(self::MESSAGE_EMPTY, self::CODE_NOT_AN_ARRAY);
    }

    public static function createMalformedYamlException(): self
    {
        return new MalformedSuiteManifestException(self::MESSAGE_MALFORMED_YAML, self::CODE_MALFORMED_YAML);
    }
}
