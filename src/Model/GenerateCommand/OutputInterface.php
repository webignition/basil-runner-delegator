<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\GenerateCommand;

use webignition\BasilRunner\Model\GenerateCommandConfiguration;

interface OutputInterface extends \JsonSerializable
{
    public function getConfiguration(): GenerateCommandConfiguration;
    public function getCode(): int;

    /**
     * @return array<mixed>
     */
    public function getData(): array;
}
