<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

abstract class AbstractGenerateCommandOutput implements \JsonSerializable
{
    private $source;
    private $target;

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

    public function jsonSerialize(): array
    {
        return [
            'config' => [
                'source' => $this->source,
                'target' => $this->target,
            ],
        ];
    }
}
