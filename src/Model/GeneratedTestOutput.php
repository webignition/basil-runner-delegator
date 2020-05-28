<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class GeneratedTestOutput implements \JsonSerializable
{
    private string $source;
    private string $target;

    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
           'source' => $this->source,
           'target' => $this->target,
        ];
    }

    /**
     * @param array<string, string> $data
     *
     * @return GeneratedTestOutput
     */
    public static function fromArray(array $data): GeneratedTestOutput
    {
        return new GeneratedTestOutput($data['source'], $data['target']);
    }
}
