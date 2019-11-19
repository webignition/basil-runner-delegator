<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\Validator\Command;

use phpmock\mockery\PHPMockery;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Model\ValidationResult\Command\GenerateCommandValidationResult;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\Validator\Command\GenerateCommandValidator;

class GenerateCommandValidatorTest extends \PHPUnit\Framework\TestCase
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
     */
    public function testValidate(
        ?string $source,
        string $rawSource,
        ?string $target,
        string $rawTarget,
        GenerateCommandValidationResult $expectedResult
    ) {
        $validator = new GenerateCommandValidator();
        $result = $validator->validate($source, $rawSource, $target, $rawTarget);

        $this->assertEquals($expectedResult, $result);
    }

    public function validateSourceDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';

        return [
            'valid' => [
                'source' => $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'rawSource' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'target' => $target,
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(true)
            ],
            'source empty' => [
                'source' => null,
                'rawSource' => '',
                'target' => $target,
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_EMPTY
                )
            ],
            'source does not exist' => [
                'source' => null,
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'target' => $target,
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST
                ),
            ],
            'source not a file, is a directory' => [
                'source' => $root . '/tests/Fixtures/basil/Test/',
                'rawSource' => '/tests/Fixtures/basil/Test/',
                'target' => $target,
                'rawTarget' => $rawTarget,
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_A_FILE
                ),
            ],
        ];
    }

    public function validateTargetDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';

        return [
            'target empty' => [
                'source' => $source,
                'rawSource' => $rawSource,
                'target' => null,
                'rawTarget' => '',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_TARGET_EMPTY
                )
            ],
            'target does not exist' => [
                'source' => $source,
                'rawSource' => $rawSource,
                'target' => null,
                'rawTarget' => '/tests/build/target/non-existent',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_DOES_NOT_EXIST
                ),
            ],
            'target not a directory, is a file' => [
                'source' => $source,
                'rawSource' => $rawSource,
                'target' => $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'rawTarget' => '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_A_DIRECTORY
                ),
            ],
        ];
    }

    public function testValidateSourceFailureSourceNotReadable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';

        $validator = new GenerateCommandValidator();

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_readable')->andReturn(false);

        $result = $validator->validate($source, $rawSource, $target, $rawTarget);

        $expectedResult = new GenerateCommandValidationResult(
            false,
            GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_READABLE
        );

        $this->assertEquals($expectedResult, $result);

        \Mockery::close();
    }

    public function testValidateTargetFailureTargetNotWritable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';
        $rawTarget = 'tests/build/target';

        $validator = new GenerateCommandValidator();

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_writable')->andReturn(false);

        $result = $validator->validate($source, $rawSource, $target, $rawTarget);

        $expectedResult = new GenerateCommandValidationResult(
            false,
            GenerateCommandErrorOutput::ERROR_CODE_TARGET_INVALID_NOT_WRITABLE
        );

        $this->assertEquals($expectedResult, $result);

        \Mockery::close();
    }
}
