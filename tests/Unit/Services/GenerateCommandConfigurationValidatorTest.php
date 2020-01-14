<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Model\GenerateCommandErrorOutput;
use webignition\BasilRunner\Services\GenerateCommandConfigurationValidator;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class GenerateCommandConfigurationValidatorTest extends AbstractBaseTest
{
    /**
     * @var GenerateCommandConfigurationValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new GenerateCommandConfigurationValidator();
    }

    public function testIsValidSourceNotReadable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Services', 'is_readable')->andReturn(false);

        $this->assertFalse($this->validator->isValid($configuration));
    }

    public function testIsValidTargetNotWritable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Services', 'is_writable')->andReturn(false);

        $this->assertFalse($this->validator->isValid($configuration));
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(GenerateCommandConfiguration $configuration, bool $expectedIsValid)
    {
        $this->assertSame($expectedIsValid, $this->validator->isValid($configuration));
    }

    public function isValidDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();

        $source = $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml';
        $target = $root . '/tests/build/target';

        return [
            'valid' => [
                'configuration' => new GenerateCommandConfiguration($source, $target, TestCase::class),
                'expectedIsValid' => true,
            ],
            'invalid: source is empty' => [
                'configuration' => new GenerateCommandConfiguration('', $target, TestCase::class),
                'expectedIsValid' => false,
            ],
            'invalid: target is empty' => [
                'configuration' => new GenerateCommandConfiguration($source, '', TestCase::class),
                'expectedIsValid' => false,
            ],
            'invalid: target is not a directory, is a file' => [
                'configuration' => new GenerateCommandConfiguration($source, $source, TestCase::class),
                'expectedIsValid' => false,
            ],
            'invalid: base class does not exist' => [
                'configuration' => new GenerateCommandConfiguration($source, $target, 'Foo'),
                'expectedIsValid' => false,
            ],
        ];
    }

    public function testDeriveInvalidConfigurationErrorCodeSourceNotReadable()
    {
        $root = (new ProjectRootPathProvider())->get();

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Services', 'is_readable')->andReturn(false);

        $this->assertSame(
            GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_SOURCE_INVALID_NOT_READABLE,
            $this->validator->deriveInvalidConfigurationErrorCode($configuration)
        );
    }

    public function testDeriveInvalidConfigurationErrorCodeValidTargetNotWritable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Services', 'is_writable')->andReturn(false);

        $this->assertSame(
            GenerateCommandErrorOutput::CODE_COMMAND_CONFIG_TARGET_INVALID_NOT_WRITABLE,
            $this->validator->deriveInvalidConfigurationErrorCode($configuration)
        );
    }
}
