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
    private $validator;

    public function setUp(): void
    {
        parent::setUp();

        PHPMockery::define('webignition\BasilRunner\Services\Validator\Command', 'is_readable');
        PHPMockery::define('webignition\BasilRunner\Services\Validator\Command', 'is_writable');

        $this->validator = new GenerateCommandValidator();
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(
        GenerateCommandConfiguration $configuration,
        string $rawSource,
        string $rawTarget,
        bool $expectedIsValid
    ) {
        $this->assertSame($expectedIsValid, $this->validator->isValid($configuration, $rawSource, $rawTarget));
    }

    public function isValidDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';

        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';

        $baseClass = TestCase::class;

        $emptySourceConfiguration = new GenerateCommandConfiguration(
            '',
            $target,
            $baseClass
        );

        $emptyTargetConfiguration = new GenerateCommandConfiguration(
            $source,
            '',
            $baseClass
        );

        return [
            'valid' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $target,
                    $baseClass
                ),
                'rawSource' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'rawTarget' => $rawTarget,
                'expectedIsValid' => true,
            ],
            'source empty' => [
                'configuration' => $emptySourceConfiguration,
                'rawSource' => '',
                'rawTarget' => $rawTarget,
                'expectedIsValid' => false,
            ],
            'source does not exist' => [
                'configuration' => $emptySourceConfiguration,
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'rawTarget' => $rawTarget,
                'expectedIsValid' => false,
            ],
            'target empty' => [
                'configuration' => $emptyTargetConfiguration,
                'rawSource' => $rawSource,
                'rawTarget' => '',
                'expectedIsValid' => false,
            ],
            'target does not exist' => [
                'configuration' => $emptyTargetConfiguration,
                'rawSource' => $rawSource,
                'rawTarget' => '/tests/build/target/non-existent',
                'expectedIsValid' => false,
            ],
            'target not a directory, is a file' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                    $baseClass
                ),
                'rawSource' => $rawSource,
                'rawTarget' => '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'expectedIsValid' => false,
            ],
            'base class does not exist' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $target,
                    'Foo'
                ),
                'rawSource' => $rawSource,
                'rawTarget' => $rawTarget,
                'expectedIsValid' => false,
            ],
        ];
    }

    /**
     * @dataProvider createValidationResultDataProvider
     */
    public function testCreateValidationResult(
        GenerateCommandConfiguration $configuration,
        string $rawSource,
        string $rawTarget,
        ?GenerateCommandValidationResult $expectedValidationResult
    ) {
        $this->assertEquals(
            $expectedValidationResult,
            $this->validator->createValidationResult($configuration, $rawSource, $rawTarget)
        );
    }

    public function createValidationResultDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';

        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';

        $baseClass = TestCase::class;

        $emptySourceConfiguration = new GenerateCommandConfiguration(
            '',
            $target,
            $baseClass
        );

        $emptyTargetConfiguration = new GenerateCommandConfiguration(
            $source,
            '',
            $baseClass
        );

        return [
            'valid' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $target,
                    $baseClass
                ),
                'rawSource' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'rawTarget' => $rawTarget,
                'expectedValidationResult' => null,
            ],
            'source empty' => [
                'configuration' => $emptySourceConfiguration,
                'rawSource' => '',
                'rawTarget' => $rawTarget,
                'expectedValidationResult' => new GenerateCommandValidationResult(
                    $emptySourceConfiguration,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_EMPTY
                ),
            ],
            'source does not exist' => [
                'configuration' => $emptySourceConfiguration,
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'rawTarget' => $rawTarget,
                'expectedValidationResult' => new GenerateCommandValidationResult(
                    $emptySourceConfiguration,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_DOES_NOT_EXIST
                ),
            ],
            'target empty' => [
                'configuration' => $emptyTargetConfiguration,
                'rawSource' => $rawSource,
                'rawTarget' => '',
                'expectedValidationResult' => new GenerateCommandValidationResult(
                    $emptyTargetConfiguration,
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_EMPTY
                ),
            ],
            'target does not exist' => [
                'configuration' => $emptyTargetConfiguration,
                'rawSource' => $rawSource,
                'rawTarget' => '/tests/build/target/non-existent',
                'expectedValidationResult' => new GenerateCommandValidationResult(
                    $emptyTargetConfiguration,
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
                'expectedValidationResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $source,
                        $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                        $baseClass
                    ),
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_A_DIRECTORY
                ),
            ],
            'base class does not exist' => [
                'configuration' => new GenerateCommandConfiguration(
                    $source,
                    $target,
                    'Foo'
                ),
                'rawSource' => $rawSource,
                'rawTarget' => $rawTarget,
                'expectedValidationResult' => new GenerateCommandValidationResult(
                    new GenerateCommandConfiguration(
                        $source,
                        $target,
                        'Foo'
                    ),
                    GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_BASE_CLASS_DOES_NOT_EXIST
                ),
            ],
        ];
    }

    public function testSourceNotReadable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawTarget = 'tests/build/target';

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_readable')->andReturn(false);

        $this->assertFalse($this->validator->isValid($configuration, $rawSource, $rawTarget));

        $this->assertEquals(
            new GenerateCommandValidationResult(
                $configuration,
                GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE
            ),
            $this->validator->createValidationResult($configuration, $rawSource, $rawTarget)
        );

        \Mockery::close();
    }

    public function testTargetNotWritable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawTarget = 'tests/build/target';

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_writable')->andReturn(false);

        $this->assertFalse($this->validator->isValid($configuration, $rawSource, $rawTarget));

        $this->assertEquals(
            new GenerateCommandValidationResult(
                $configuration,
                GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE
            ),
            $this->validator->createValidationResult($configuration, $rawSource, $rawTarget)
        );

        \Mockery::close();
    }
}
