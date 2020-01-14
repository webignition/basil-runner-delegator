<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\Validator\Command;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Command\GenerateCommand;
use webignition\BasilRunner\Exception\GenerateCommandValidationException;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Services\GenerateCommandConfigurationFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class GenerateCommandConfigurationFactoryTest extends TestCase
{
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new GenerateCommandConfigurationFactory(
            new GenerateCommandValidator(),
            new ProjectRootPathProvider()
        );
    }

    public function testCreateFromTypedInputForInvalidConfiguration()
    {
        $input = $this->createTypedInput('', '', '');

        try {
            $this->factory->createFromTypedInput($input);

            $this->fail('GenerateCommandValidationException not thrown');
        } catch (GenerateCommandValidationException $generateCommandValidationException) {
            $result = $generateCommandValidationException->getValidationResult();

            $this->assertEquals(
                new GenerateCommandConfiguration('', '', ''),
                $result->getConfiguration()
            );
        }
    }

    /**
     * @dataProvider createFromTypedInputSuccessDataProvider
     */
    public function testCreateFromTypedInputSuccess(
        TypedInput $input,
        GenerateCommandConfiguration $expectedConfiguration
    ) {
        $this->assertEquals($expectedConfiguration, $this->factory->createFromTypedInput($input));
    }

    public function createFromTypedInputSuccessDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'default' => [
                'input' => $this->createTypedInput(
                    'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    'tests/build/target',
                    TestCase::class
                ),
                'expectedConfiguration' => new GenerateCommandConfiguration(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $root . '/tests/build/target',
                    TestCase::class
                ),
            ],
        ];
    }

    private function createTypedInput(string $source, string $target, string $baseClass): TypedInput
    {
        $input = \Mockery::mock(TypedInput::class);
        $input
            ->shouldReceive('getStringOption')
            ->with(GenerateCommand::OPTION_SOURCE)
            ->andReturn($source);

        $input
            ->shouldReceive('getStringOption')
            ->with(GenerateCommand::OPTION_TARGET)
            ->andReturn($target);

        $input
            ->shouldReceive('getStringOption')
            ->with(GenerateCommand::OPTION_BASE_CLASS)
            ->andReturn($baseClass);

        return $input;
    }
}
