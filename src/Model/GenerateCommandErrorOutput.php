<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class GenerateCommandErrorOutput extends AbstractGenerateCommandOutput implements \JsonSerializable
{
    public const ERROR_CODE_SOURCE_EMPTY = 1;
    public const ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST = 2;
    public const ERROR_CODE_SOURCE_INVALID_NOT_A_FILE = 3;
    public const ERROR_CODE_SOURCE_INVALID_NOT_READABLE = 4;
    public const ERROR_CODE_TARGET_EMPTY = 5;
    public const ERROR_CODE_TARGET_INVALID_DOES_NOT_EXIST = 6;
    public const ERROR_CODE_TARGET_INVALID_NOT_A_DIRECTORY = 7;
    public const ERROR_CODE_TARGET_INVALID_NOT_WRITABLE = 8;
    public const ERROR_CODE_BASE_CLASS_DOES_NOT_EXIST = 9;

    private $errorMessage;

    public function __construct(string $source, string $target, string $baseClass, string $errorMessage)
    {
        parent::__construct($source, $target, $baseClass);

        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function jsonSerialize(): array
    {
        $serializedData = parent::jsonSerialize();
        $serializedData['error'] = $this->errorMessage;

        return $serializedData;
    }

    public static function fromJson(string $json): GenerateCommandErrorOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];

        return new GenerateCommandErrorOutput(
            $configData['source'],
            $configData['target'],
            $configData['base-class'],
            $data['error']
        );
    }
}
