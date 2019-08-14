<?php declare(strict_types=1);

namespace webignition\BasilRunner\Bin;

class VendorAutoloadFinder
{
    private static $autoloadRelativePaths = [
        '/../../autoload.php',
        '/../vendor/autoload.php',
        '/vendor/autoload.php',
    ];

    public static function findAutoloadPath(): ?string
    {
        foreach (self::$autoloadRelativePaths as $autoloadRelativePath) {
            $autoloadPath = __DIR__ . $autoloadRelativePath;

            if (file_exists($autoloadPath)) {
                return realpath($autoloadPath);
            }
        }

        return null;
    }
}
