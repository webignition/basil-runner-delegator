<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit;

use phpmock\mockery\PHPMockery;

abstract class AbstractBaseTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
}
