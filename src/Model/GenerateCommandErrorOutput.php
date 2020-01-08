<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class GenerateCommandErrorOutput extends AbstractGenerateCommandOutput implements \JsonSerializable
{
    public const CODE_COMMAND_CONFIG_SOURCE_EMPTY = 100;
    public const CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST = 101;
    public const CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE = 102;
    public const CODE_COMMAND_CONFIG_TARGET_EMPTY = 103;
    public const CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST = 104;
    public const CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY = 105;
    public const CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE = 106;
    public const CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST = 107;

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

    /**
     * @return array<mixed>
     */
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
