<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Util\Printer;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class ResultPrinter extends Printer implements TestListener
{
    private const DEFAULT_ICON = '?';

    private $formatter;

    /**
     * @var string
     */
    private $projectRootPath = '';

    /**
     * @var string
     */
    private $currentTestPath = '';

    /**
     * @var array<int, string>
     */
    private $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public function __construct($out = null)
    {
        parent::__construct($out);

        $this->formatter = Formatter::create();
        $projectRootPath = (ProjectRootPathProvider::create())->get();

        $this->projectRootPath = $projectRootPath;
    }

    /**
     * @inheritDoc
     */
    public function addError(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addError() method.
    }

    /**
     * @inheritDoc
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
        // TODO: Implement addWarning() method.
    }

    /**
     * @inheritDoc
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        // TODO: Implement addFailure() method.
    }

    /**
     * @inheritDoc
     */
    public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addIncompleteTest() method.
    }

    /**
     * @inheritDoc
     */
    public function addRiskyTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addRiskyTest() method.
    }

    /**
     * @inheritDoc
     */
    public function addSkippedTest(Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addSkippedTest() method.
    }

    /**
     * @param TestSuite<Test> $suite
     */
    public function startTestSuite(TestSuite $suite): void
    {
        // TODO: Implement startTestSuite() method.
    }

    /**
     * @param TestSuite<Test> $suite
     */
    public function endTestSuite(TestSuite $suite): void
    {
        // TODO: Implement endTestSuite() method.
    }

    /**
     * @inheritDoc
     */
    public function startTest(Test $test): void
    {
        if ($test instanceof BasilTestCaseInterface) {
            $testPath = $test::getBasilTestPath();

            if ($this->currentTestPath !== $testPath) {
                $this->currentTestPath = $testPath;

                $relativePath = substr($testPath, strlen($this->projectRootPath) + 1);

                $this->write($this->formatter->makeBold($relativePath));
                $this->writeEmptyLine();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function endTest(Test $test, float $time): void
    {
        if ($test instanceof BasilTestCaseInterface) {
            $content = sprintf(
                '  %s %s',
                $this->getEndTestIcon($test),
                $test->getBasilStepName()
            );

            $this->write($content);
            $this->writeEmptyLine();
        }
    }

    private function getEndTestIcon(Test $test): string
    {
        return $this->icons[$this->getTestEndStatus($test)] ?? self::DEFAULT_ICON;
    }

    private function getTestEndStatus(Test $test): int
    {
        if ($test instanceof TestCase || $test instanceof BasilTestCaseInterface) {
            return $test->getStatus();
        }

        return BaseTestRunner::STATUS_UNKNOWN;
    }

    private function writeEmptyLine(): void
    {
        $this->write("\n");
    }
}
