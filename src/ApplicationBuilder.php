<?php declare(strict_types=1);

namespace webignition\BasilRunner;

use Symfony\Component\Console\Application;

class ApplicationBuilder
{
    private const NAME = 'Basil Runner';

    private $version;

    public function __construct()
    {
        $this->version = '0.1-beta';
    }

    public function createApplication(): Application
    {
        return new Application(self::NAME, $this->version);
    }

    public static function build(): Application
    {
        return (new static())->createApplication();
    }
}