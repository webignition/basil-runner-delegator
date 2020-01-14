<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class ErrorContext implements \JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    private $detail = [];

    /**
     * @param array<string, mixed> $detail
     */
    public function __construct(array $detail = [])
    {
        $this->detail = $detail;
    }

    /**
     * @return array<string, int|string|array>
     */
    public function jsonSerialize(): array
    {
        return $this->detail;
    }

    /**
     * @param array<mixed> $data
     *
     * @return ErrorContext
     */
    public static function fromData(array $data): ErrorContext
    {
        return new ErrorContext($data);
    }
}
