<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class GenerateCommandConfiguration implements \JsonSerializable
{
    private $source;
    private $target;
    private $baseClass;

    public function __construct(
        string $source,
        string $target,
        string $baseClass
    ) {
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

    public function isValid(): bool
    {
        if ('' === $this->source) {
            return false;
        }

        if (!is_readable($this->source)) {
            return false;
        }

        if ('' === $this->target) {
            return false;
        }

        if (!is_dir($this->target)) {
            return false;
        }

        if (!is_writable($this->target)) {
            return false;
        }

        if (!class_exists($this->baseClass)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'source' => $this->source,
            'target' => $this->target,
            'base-class' => $this->baseClass,
        ];
    }
}
