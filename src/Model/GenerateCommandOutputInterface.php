<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

interface GenerateCommandOutputInterface extends \JsonSerializable
{
    public function getConfiguration(): GenerateCommandConfiguration;
    public function getCode(): int;

    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
