<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services;

use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\BasilRunner\Model\GenerateCommandConfiguration;
use webignition\BasilRunner\Services\ProjectRootPathProvider;

class GenerateCommandConfigurationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        PHPMockery::define('webignition\BasilRunner\Model', 'is_readable');
        PHPMockery::define('webignition\BasilRunner\Model', 'is_writable');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(GenerateCommandConfiguration $configuration, bool $expectedIsValid)
    {
        $this->assertSame($expectedIsValid, $configuration->isValid());
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

    public function testIsValidSourceNotReadable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Model', 'is_readable')->andReturn(false);

        $this->assertFalse($configuration->isValid());

        \Mockery::close();
    }

    public function testIsValidTargetNotWritable(): void
    {
        $root = (new ProjectRootPathProvider())->get();

        $configuration = new GenerateCommandConfiguration(
            $root . '/tests/Fixtures/basil/Test/example.com.verify-open-literal.yml',
            $root . '/tests/build/target',
            TestCase::class
        );

        PHPMockery::mock('webignition\BasilRunner\Model', 'is_writable')->andReturn(false);

        $this->assertFalse($configuration->isValid());

        \Mockery::close();
    }
}
