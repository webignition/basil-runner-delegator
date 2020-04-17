<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Util\Printer;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryLineFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;

class ResultPrinter extends Printer implements TestListener
{
    /**
     * @var ConsoleOutputFactory
     */
    private $consoleOutputFactory;

    /**
     * @var ActivityLineFactory
     */
    private $activityLineFactory;

    /**
     * @var string
     */
    private $projectRootPath = '';

    /**
     * @var string
     */
    private $currentTestPath = '';

    /**
     * @var bool
     */
    private $isFirstTest;

    /**
     * @var SummaryHandler
     */
    private $failedAssertionSummaryHandler;

    public function __construct($out = null)
    {
        parent::__construct($out);

        $projectRootPath = (ProjectRootPathProvider::create())->get();

        $consoleOutputFactory = new ConsoleOutputFactory();

        $this->projectRootPath = $projectRootPath;
        $this->isFirstTest = true;
        $this->consoleOutputFactory = $consoleOutputFactory;
        $this->activityLineFactory = new ActivityLineFactory($consoleOutputFactory);
        $this->failedAssertionSummaryHandler = new SummaryHandler(
            DomIdentifierFactory::createFactory(),
            new SummaryLineFactory($consoleOutputFactory)
        );
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

                if (false === $this->isFirstTest) {
                    $this->writeEmptyLine();
                }

                $this->write($this->consoleOutputFactory->createTestPath($relativePath));
                $this->writeEmptyLine();

                if ($this->isFirstTest) {
                    $this->isFirstTest = false;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function endTest(Test $test, float $time): void
    {
        if ($test instanceof BasilTestCaseInterface) {
            $testEndStatus = $test->getStatus();

            $stepNameLine = $this->activityLineFactory->createStepNameLine($test);

            $handledStatements = $test->getHandledStatements();
            $failedStatement = null;

            if (BaseTestRunner::STATUS_PASSED !== $testEndStatus) {
                $failedStatement = array_pop($handledStatements);
            }

            foreach ($handledStatements as $statement) {
                $stepNameLine->addChild($this->activityLineFactory->createCompletedStatementLine($statement));
            }

            if ($failedStatement instanceof StatementInterface) {
                $stepNameLine->addChild($this->activityLineFactory->createFailedStatementLine($failedStatement));

                $summaryActivityLine = null;

                if ($failedStatement instanceof AssertionInterface) {
                    $summaryActivityLine = $this->failedAssertionSummaryHandler->handle(
                        $failedStatement,
                        (string) $test->getExpectedValue(),
                        (string) $test->getExaminedValue()
                    );
                }

                if ($summaryActivityLine instanceof ActivityLine) {
                    $stepNameLine->addChild($summaryActivityLine);
                }
            }

            $this->write((string) $stepNameLine);
            $this->writeEmptyLine();
        }
    }

    private function writeEmptyLine(): void
    {
        $this->write("\n");
    }
}
