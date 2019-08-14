<?php declare(strict_types=1);

namespace webignition\BasilRunner\Bin;

require __DIR__ . '/VendorAutoloadFinder.php';

$autoloadPath = VendorAutoloadFinder::findAutoloadPath();

if (null === $autoloadPath) {
    fwrite(
        STDERR,
        'Unable to find autoload path. Have you installed dependencies using "composer install"?' . PHP_EOL . PHP_EOL
    );

    exit(1);
}

require $autoloadPath;

