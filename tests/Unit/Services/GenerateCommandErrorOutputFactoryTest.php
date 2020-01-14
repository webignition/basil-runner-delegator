<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\Validator\Command;

use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Services\GenerateCommandErrorOutputFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class GenerateCommandErrorOutputFactoryTest extends TestCase
{
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        PHPMockery::define('webignition\BasilRunner\Services', 'is_readable');
        PHPMockery::define('webignition\BasilRunner\Services', 'is_writable');

        $this->factory = new GenerateCommandErrorOutputFactory();
    }

    /**
     * @dataProvider createFromInvalidConfigurationDataProvider
     */
    public function testCreateFromInvalidConfiguration(
        GenerateCommandConfiguration $configuration,
        GenerateCommandErrorOutput $expectedOutput
    ) {
        $this->assertEquals($expectedOutput, $this->factory->createFromInvalidConfiguration($configuration));
    }

    public function createFromInvalidConfigurationDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';
        $baseClass = TestCase::class;

        return [
            'source does not exist' => [
                'configuration' => new GenerateCommandConfiguration('', $target, $baseClass),
                'expectedOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration('', $target, $baseClass),
                    'source invalid; does not exist',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST
                ),
            ],
            'target does not exist' => [
                'configuration' => new GenerateCommandConfiguration($source, '', $baseClass),
                'expectedOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration($source, '', $baseClass),
                    'target invalid; does not exist',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST
                ),
            ],
            'target not a directory' => [
                'configuration' => new GenerateCommandConfiguration($source, $source, $baseClass),
                'expectedOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration($source, $source, $baseClass),
                    'target invalid; is not a directory (is it a file?)',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY
                ),
            ],
            'base class does not exist' => [
                'configuration' => new GenerateCommandConfiguration($source, $target, 'Foo'),
                'expectedOutput' => new GenerateCommandErrorOutput(
                    new GenerateCommandConfiguration($source, $target, 'Foo'),
                    'base class invalid: does not exist',
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                ),
            ],
        ];
    }

    public function testCreateFromInvalidConfigurationSourceNotReadable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';

        $configuration = new GenerateCommandConfiguration($source, $target, TestCase::class);

        $expectedOutput = new GenerateCommandErrorOutput(
            $configuration,
            'source invalid; file is not readable',
            GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE
        );

        PHPMockery::mock('webignition\BasilRunner\Services', 'is_readable')->andReturn(false);

        $this->assertEquals($expectedOutput, $this->factory->createFromInvalidConfiguration($configuration));

        \Mockery::close();
    }

    public function testCreateFromInvalidConfigurationTargetNotWritable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';

        $configuration = new GenerateCommandConfiguration($source, $target, TestCase::class);

        $expectedOutput = new GenerateCommandErrorOutput(
            $configuration,
            'target invalid; directory is not writable',
            GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE
        );

        PHPMockery::mock('webignition\BasilRunner\Services', 'is_writable')->andReturn(false);

        $this->assertEquals($expectedOutput, $this->factory->createFromInvalidConfiguration($configuration));

        \Mockery::close();
    }
}
