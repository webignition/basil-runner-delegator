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
    private $errorCode;
    private $errorContext;

    public function __construct(
        GenerateCommandConfiguration $configuration,
        string $errorMessage,
        int $errorCode,
        ?ErrorContext $errorContext = null
    ) {
        parent::__construct($configuration);

        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
        $this->errorContext = $errorContext;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        $serializedData = parent::jsonSerialize();
        $serializedData['message'] = $this->errorMessage;
        $serializedData['code'] = $this->errorCode;

        if (null !== $this->errorContext) {
            $serializedData['context'] = $this->errorContext->jsonSerialize();
        }

        return $serializedData;
    }

    public static function fromJson(string $json): GenerateCommandErrorOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'];
        $contextData = $data['context'] ?? [];

        $context = [] === $contextData
            ? null
            : ErrorContext::fromData($contextData);

        return new GenerateCommandErrorOutput(
            new GenerateCommandConfiguration(
                $configData['source'],
                $configData['target'],
                $configData['base-class']
            ),
            $data['message'],
            (int) $data['code'],
            $context
        );
    }
}
