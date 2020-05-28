<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;

class Test
{
    private BasilTestCaseInterface $test;
    private string $testPath;
    private string $projectRootPath;

    public function __construct(BasilTestCaseInterface $test, string $testPath, string $projectRootPath)
    {
        $this->test = $test;
        $this->testPath = $testPath;
        $this->projectRootPath = $projectRootPath;
    }

    public function hasPath(string $path): bool
    {
        return $this->testPath === $path;
    }

    public function getRelativePath(): string
    {
        return substr($this->testPath, strlen($this->projectRootPath) + 1);
    }
}
