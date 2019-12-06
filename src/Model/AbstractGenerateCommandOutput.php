<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

abstract class AbstractGenerateCommandOutput implements \JsonSerializable
{
    private $source;
    private $target;
    private $baseClass;

    public function __construct(string $source, string $target, string $baseClass)
    {
        $this->source = $source;
        $this->target = $target;
        $this->baseClass = $baseClass;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getBaseClass(): string
    {
        return $this->baseClass;
    }

    /**
     * @return array<string, array|string>
     */
    public function jsonSerialize(): array
    {
        return [
            'config' => [
                'source' => $this->source,
                'target' => $this->target,
                'base-class' => $this->baseClass,
            ],
        ];
    }
}
