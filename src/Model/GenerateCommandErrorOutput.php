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
    public const CODE_LOADER_EXCEPTION = 200;
    public const CODE_RESOLVER_EXCEPTION = 300;

    private $message;

    /**
     * @var array<mixed>
     */
    private $context;

    /**
     * @param GenerateCommandConfiguration $configuration
     * @param string $message
     * @param int $code
     * @param array<mixed> $context
     */
    public function __construct(
        GenerateCommandConfiguration $configuration,
        string $message,
        int $code,
        array $context = []
    ) {
        parent::__construct($configuration, self::STATUS_FAILURE, $code);

        $this->message = $message;
        $this->context = $context;
    }

    /**
     * @return array<string, int|string>
     */
    public function jsonSerialize(): array
    {
        $errorData = [
            'message' => $this->message,
            'code' => $this->getCode(),
        ];

        if ([] !== $this->context) {
            $errorData['context'] = $this->context;
        }

        $serializedData = parent::jsonSerialize();
        $serializedData['error'] = $errorData;

        return $serializedData;
    }

    public static function fromJson(string $json): GenerateCommandErrorOutput
    {
        $data = json_decode($json, true);
        $configData = $data['config'] ?? [];
        $errorData = $data['error'] ?? [];
        $contextData = $errorData['context'] ?? [];

        return new GenerateCommandErrorOutput(
            new GenerateCommandConfiguration(
                $configData['source'],
                $configData['target'],
                $configData['base-class']
            ),
            $errorData['message'],
            (int) $errorData['code'],
            $contextData
        );
    }
}
