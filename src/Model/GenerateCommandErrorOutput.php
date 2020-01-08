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
    private $errorContext;

    public function __construct(
        string $source,
        string $target,
        string $baseClass,
        string $errorMessage,
        ErrorContext $errorContext
    ) {
        parent::__construct($source, $target, $baseClass);

        $this->errorMessage = $errorMessage;
        $this->errorContext = $errorContext;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        $serializedData = parent::jsonSerialize();
        $serializedData['error'] = $this->errorMessage;
        $serializedData['context'] = $this->errorContext->jsonSerialize();

        return $serializedData;
    }

    public static function fromJson(string $json): GenerateCommandErrorOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];

//        var_dump($json, $data, ErrorContext::fromData($data['context']));
//        exit();

        return new GenerateCommandErrorOutput(
            $configData['source'],
            $configData['target'],
            $configData['base-class'],
            $data['error'],
            ErrorContext::fromData($data['context'])
        );
    }
}
