<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

class IconMap
{
    private const DEFAULT = '?';

    /**
     * @var array<int, string>
     */
    private static array $icons = [
        Status::SUCCESS => '✓',
        Status::FAILURE => 'x',
    ];

    public static function get(int $status): string
    {
        return self::$icons[$status] ?? self::DEFAULT;
    }
}
