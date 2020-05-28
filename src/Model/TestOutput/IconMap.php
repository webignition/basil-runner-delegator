<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

use PHPUnit\Runner\BaseTestRunner;

class IconMap
{
    private const DEFAULT = '?';

    /**
     * @var array<int, string>
     */
    private static array $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public static function get(int $status): string
    {
        return self::$icons[$status] ?? self::DEFAULT;
    }
}
