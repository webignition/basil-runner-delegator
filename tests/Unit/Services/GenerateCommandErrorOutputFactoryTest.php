<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommand\ErrorOutput;
use webignition\BasilRunner\Services\GenerateCommandConfigurationValidator;
use webignition\BasilRunner\Services\GenerateCommandErrorOutputFactory;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class GenerateCommandErrorOutputFactoryTest extends AbstractBaseTest
{
    /**
     * @dataProvider createFromInvalidConfigurationDataProvider
     */
    public function testCreateFromInvalidConfiguration(
        GenerateCommandConfiguration $configuration,
        GenerateCommandConfigurationValidator $generateCommandConfigurationValidator,
        ErrorOutput $expectedOutput
    ) {
        $factory = new GenerateCommandErrorOutputFactory(
            $generateCommandConfigurationValidator
        );

        $this->assertEquals($expectedOutput, $factory->createFromInvalidConfiguration($configuration));
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
                'generateCommandConfigurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    new GenerateCommandConfiguration('', $target, $baseClass),
                    ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST
                ),
                'expectedOutput' => new ErrorOutput(
                    new GenerateCommandConfiguration('', $target, $baseClass),
                    'source invalid; does not exist',
                    ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST
                ),
            ],
            'source not readable' => [
                'configuration' => new GenerateCommandConfiguration('', $target, $baseClass),
                'generateCommandConfigurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    new GenerateCommandConfiguration('', $target, $baseClass),
                    ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE
                ),
                'expectedOutput' => new ErrorOutput(
                    new GenerateCommandConfiguration('', $target, $baseClass),
                    'source invalid; file is not readable',
                    ErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE
                ),
            ],
            'target does not exist' => [
                'configuration' => new GenerateCommandConfiguration($source, '', $baseClass),
                'generateCommandConfigurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    new GenerateCommandConfiguration($source, '', $baseClass),
                    ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST
                ),
                'expectedOutput' => new ErrorOutput(
                    new GenerateCommandConfiguration($source, '', $baseClass),
                    'target invalid; does not exist',
                    ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST
                ),
            ],
            'target not writable' => [
                'configuration' => new GenerateCommandConfiguration($source, '', $baseClass),
                'generateCommandConfigurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    new GenerateCommandConfiguration($source, '', $baseClass),
                    ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE
                ),
                'expectedOutput' => new ErrorOutput(
                    new GenerateCommandConfiguration($source, '', $baseClass),
                    'target invalid; directory is not writable',
                    ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE
                ),
            ],
            'target not a directory' => [
                'configuration' => new GenerateCommandConfiguration($source, $source, $baseClass),
                'generateCommandConfigurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    new GenerateCommandConfiguration($source, $source, $baseClass),
                    ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY
                ),
                'expectedOutput' => new ErrorOutput(
                    new GenerateCommandConfiguration($source, $source, $baseClass),
                    'target invalid; is not a directory (is it a file?)',
                    ErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY
                ),
            ],
            'base class does not exist' => [
                'configuration' => new GenerateCommandConfiguration($source, $target, 'Foo'),
                'generateCommandConfigurationValidator' => $this->createGenerateCommandConfigurationValidator(
                    new GenerateCommandConfiguration($source, $target, 'Foo'),
                    ErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                ),
                'expectedOutput' => new ErrorOutput(
                    new GenerateCommandConfiguration($source, $target, 'Foo'),
                    'base class invalid: does not exist',
                    ErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                ),
            ],
        ];
    }

    private function createGenerateCommandConfigurationValidator(
        GenerateCommandConfiguration $expectedConfiguration,
        int $errorCode
    ): GenerateCommandConfigurationValidator {
        $validator = \Mockery::mock(GenerateCommandConfigurationValidator::class);

        $validator
            ->shouldReceive('deriveInvalidConfigurationErrorCode')
            ->withArgs(function (GenerateCommandConfiguration $configuration) use ($expectedConfiguration) {
                $this->assertEquals($expectedConfiguration, $configuration);

                return true;
            })
            ->andReturn($errorCode);

        return $validator;
    }
}
