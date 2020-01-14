<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\Validator\Command;

use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;

class GenerateCommandValidatorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        PHPMockery::define('webignition\BasilRunner\Services\Validator\Command', 'is_readable');
        PHPMockery::define('webignition\BasilRunner\Services\Validator\Command', 'is_writable');
    }

    /**
     * @dataProvider validateSourceDataProvider
     * @dataProvider validateTargetDataProvider
     * @dataProvider validateBaseClassDataProvider
     */
    public function testValidate(
        GenerateCommandConfiguration $configuration,
        string $rawSource,
        string $rawTarget,
        GenerateCommandValidationResult $expectedResult
    ): void {
        $validator = new GenerateCommandValidator();
        $result = $validator->validate($configuration, $rawSource, $rawTarget);

        $this->assertEquals($expectedResult, $result);
    }

    public function validateSourceDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';
        $baseClass = TestCase::class;

        return [
            'valid' => [
                'configuration' => new GenerateCommandConfiguration(
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $target,
                    $baseClass
                ),
                'rawSource' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        $target,
                        $baseClass
                    ),
                    true
                )
            ],
            'source empty' => [
                'configuration' => new GenerateCommandConfiguration(
                    '',
                    $target,
                    $baseClass
                ),
                'rawSource' => '',
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        '',
                        $target,
                        $baseClass
                    ),
                    false,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY
                )
            ],
            'source does not exist' => [
                'configuration' => new GenerateCommandConfiguration(
                    '',
                    $target,
                    $baseClass
                ),
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        '',
                        $target,
                        $baseClass
                    ),
                    false,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST
                ),
            ],
        ];
    }

    public function validateTargetDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $baseClass = TestCase::class;

        return [
            'target empty' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    '',
                    $baseClass
                ),
                'rawSource' => $rawSource,
                'rawTarget' => '',
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $source,
                        '',
                        $baseClass
                    ),
                    false,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY
                )
            ],
            'target does not exist' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    '',
                    $baseClass
                ),
                'rawSource' => $rawSource,
                'rawTarget' => '/tests/build/target/non-existent',
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $source,
                        '',
                        $baseClass
                    ),
                    false,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_DOES_NOT_EXIST
                ),
            ],
            'target not a directory, is a file' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $baseClass
                ),
                'rawSource' => $rawSource,
                'rawTarget' => '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $source,
                        $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        $baseClass
                    ),
                    false,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY
                ),
            ],
        ];
    }

    public function validateBaseClassDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';

        return [
            'base class does not exist' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $target,
                    'Foo'
                ),
                'rawSource' => $rawSource,
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $source,
                        $target,
                        'Foo'
                    ),
                    false,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                )
            ],
        ];
    }


    public function testValidateSourceFailureSourceNotReadable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawTarget = 'tests/build/target';

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        $validator = new GenerateCommandValidator();

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_readable')->andReturn(false);

        $result = $validator->validate($configuration, $rawSource, $rawTarget);

        $expectedResult = new GenerateCommandValidationResult(
            $configuration,
            false,
            GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE
        );

        $this->assertEquals($expectedResult, $result);

        \Mockery::close();
    }

    public function testValidateTargetFailureTargetNotWritable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawTarget = 'tests/build/target';

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        $validator = new GenerateCommandValidator();

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_writable')->andReturn(false);

        $result = $validator->validate($configuration, $rawSource, $rawTarget);

        $expectedResult = new GenerateCommandValidationResult(
            $configuration,
            false,
            GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE
        );

        $this->assertEquals($expectedResult, $result);

        \Mockery::close();
    }
}
