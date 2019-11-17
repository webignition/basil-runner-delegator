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
        ?string $target,
        string $rawSource,
        GenerateCommandValidationResult $expectedResult
    ) {
        $validator = new GenerateCommandValidator();
        $result = $validator->validateSource($source, $target, $rawSource);

        $this->assertEquals($expectedResult, $result);
    }

    public function validateSourceDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        return [
            'source exists, is a file, is readable' => [
                'source' => $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'target' => $root . '/tests/build/target',
                'rawSource' => 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
                'expectedResult' => new GenerateCommandValidationResult(true)
            ],
            'source missing' => [
                'source' => null,
                'target' => $root . '/tests/build/target',
                'rawSource' => '',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    new GenerateCommandErrorOutput(
                        '',
                        $root . '/tests/build/target',
                        'source empty; call with --source=SOURCE'
                    ),
                    1
                )
            ],
            'source does not exist, target valid' => [
                'source' => null,
                'target' => $root . '/tests/build/target',
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    new GenerateCommandErrorOutput(
                        '',
                        $root . '/tests/build/target',
                        'source invalid; does not exist'
                    ),
                    2
                ),
            ],
            'source not a file, is a directory' => [
                'source' => $root . '/tests/Fixtures/basil/Test/',
                'target' => $root . '/tests/build/target',
                'rawSource' => '/tests/Fixtures/basil/Test/non-existent.yml',
                'expectedResult' => new GenerateCommandValidationResult(
                    false,
                    new GenerateCommandErrorOutput(
                        $root . '/tests/Fixtures/basil/Test/',
                        $root . '/tests/build/target',
                        'source invalid; is not a file (is it a directory?)'
                    ),
                    3
                ),
            ],
        ];
    }

    public function testValidateSourceFailureSourceNotReadable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';
        $rawSource = 'tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';

        $validator = new GenerateCommandValidator();

        PHPMockery::mock('webignition\BasilRunner\Services\Validator\Command', 'is_readable')->andReturn(false);

        $result = $validator->validateSource($source, $target, $rawSource);

        $expectedResult = new GenerateCommandValidationResult(
            false,
            new GenerateCommandErrorOutput(
                $source,
                $target,
                'source invalid; file is not readable'
            ),
            4
        );

        $this->assertEquals($expectedResult, $result);

        \Mockery::close();
    }

}
