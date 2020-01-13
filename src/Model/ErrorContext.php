<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class ErrorContext implements \JsonSerializable
{
    public const CODE_COMMAND_CONFIG = 100;
    public const CODE_LOADER = 101;
    public const CODE_RESOLVER = 102;
    public const COMMAND_CONFIG = 'command-config';
    public const LOADER = 'loader';
    public const RESOLVER = 'resolver';

    private $name = '';
    private $contextCode = 0;
    private $errorCode = 0;

    /**
     * @var array<string, mixed>
     */
    private $detail = [];

    /**
     * @param string $name
     * @param int $contextCode
     * @param int $errorCode
     * @param array<string, mixed> $detail
     */
    public function __construct(string $name, int $contextCode, int $errorCode, array $detail = [])
    {
        $this->name = $name;
        $this->contextCode = $contextCode;
        $this->errorCode = $errorCode;
        $this->detail = $detail;
    }

    /**
     * @return array<string, int|string|array>
     */
    public function jsonSerialize(): array
    {
        $serializedData = [
            'name' => $this->name,
            'code' => $this->contextCode . ':' . $this->errorCode,
        ];

        if ([] !== $this->detail) {
            $serializedData['detail'] = $this->detail;
        }

        return $serializedData;
    }

    /**
     * @param array<mixed> $data
     *
     * @return ErrorContext
     */
    public static function fromData(array $data): ErrorContext
    {
        $codeData = $data['code'];
        $codeDataParts = explode(':', $codeData);

        $contextCode = (int) $codeDataParts[0];
        $errorCode = (int) $codeDataParts[1];

        return new ErrorContext(
            $data['name'],
            $contextCode,
            $errorCode,
            $data['detail'] ?? []
        );
    }
}
