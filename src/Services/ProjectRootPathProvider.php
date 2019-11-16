<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services;

class ProjectRootPathProvider
{
    public function get(): string
    {
        return (string) realpath(__DIR__ . '/../..');
    }
}
