<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\GenerateCommand;

interface OutputInterface extends \JsonSerializable
{
    public function getConfiguration(): Configuration;
    public function getCode(): int;

    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
