<?php

declare(strict_types=1);

namespace webignition\BasilRunnerDelegator\Exception;

class MalformedManifestException extends \Exception
{
    private const CODE_MALFORMED_YAML = 0;
    private const CODE_NOT_AN_ARRAY = 1;
    private const MESSAGE_MALFORMED_YAML = 'Content is not parsable yaml';
    private const MESSAGE_EMPTY = 'Content is not a yaml array';

    private string $content;

    public function __construct(string $message, int $code, string $content)
    {
        parent::__construct($message, $code);

        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public static function createNonArrayContentException(string $content): self
    {
        return new MalformedManifestException(
            self::MESSAGE_EMPTY,
            self::CODE_NOT_AN_ARRAY,
            $content
        );
    }

    public static function createMalformedYamlException(string $content): self
    {
        return new MalformedManifestException(
            self::MESSAGE_MALFORMED_YAML,
            self::CODE_MALFORMED_YAML,
            $content
        );
    }
}
