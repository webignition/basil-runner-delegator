<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Functional\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Model\GenerateCommandOutput;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Tests\Functional\AbstractFunctionalTest;

class GenerateCommandTest extends AbstractFunctionalTest
{
    /**
     * @var GenerateCommand
     */
    private $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = self::$container->get(GenerateCommand::class);
    }

    /**
     * @dataProvider generateDataProvider
     */
    public function testRunSuccess(array $input, array $expectedGeneratedCode)
    {
        $output = new BufferedOutput();

        $exitCode = $this->command->run(new ArrayInput($input), $output);
        $this->assertSame(0, $exitCode);

        $commandOutput = GenerateCommandOutput::fromJson($output->fetch());

        $outputData = $commandOutput->getOutput();
        $this->assertCount(1, $outputData);

        foreach ($outputData as $generatedTestOutput) {
            $this->assertSame($commandOutput->getSource(), $generatedTestOutput->getSource());
            $this->assertRegExp('/^Generated[a-f0-9]{32}Test\.php$/', $generatedTestOutput->getTarget());

            $expectedCodePath = $commandOutput->getTarget() . '/' . $generatedTestOutput->getTarget();

            $this->assertFileExists($expectedCodePath);
            $this->assertFileIsReadable($expectedCodePath);

            $this->assertEquals(
                $expectedGeneratedCode[$generatedTestOutput->getSource()],
                file_get_contents($expectedCodePath)
            );

            unlink($expectedCodePath);
        }
    }

    public function generateDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'default' => [
                'input' => [
                    '--source' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    '--target' => 'tests/build/target',
                ],
                'expectedGeneratedCode' => [
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml' =>
                        file_get_contents($root . '/tests/Fixtures/php/Test/ExampleComVerifyOpenLiteralTest.php'),
                ],
            ],
        ];
    }
}
