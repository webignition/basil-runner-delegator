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
    }

    /**
     * @dataProvider validateSourceDataProvider
     */
    public function testValidateSource(
        ?string $source,
        string $rawSource,
        GenerateCommandValidationResult $expectedResult
    ) {
        $validator = new GenerateCommandValidator();
        $result = $validator->validateSource($source, $rawSource);

        $this->assertEquals($expectedResult, $result);
    }

    public function validateSourceDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'source exists, is a file, is readable' => [
                'source' => $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'rawSource' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'expectedResult' => new GenerateCommandValidationResult(true)
            ],
            'source empty' => [
                'source' => null,
                'rawSource' => '',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_EMPTY
                )
            ],
            'source does not exist, target valid' => [
                'source' => null,
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_DOES_NOT_EXIST
                ),
            ],
            'source not a file, is a directory' => [
                'source' => $root . '/tests/Fixtures/basil/Test/',
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_A_FILE
                ),
            ],
        ];
    }

    public function testValidateSourceFailureSourceNotReadable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';

        $validator = new GenerateCommandValidator();

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_readable')->andReturn(false);

        $result = $validator->validateSource($source, $rawSource);

        $expectedResult = new GenerateCommandValidationResult(
            false,
            GenerateCommandErrorOutput::ERROR_CODE_SOURCE_INVALID_NOT_READABLE
        );

        $this->assertEquals($expectedResult, $result);

        \Mockery::close();
    }
}
