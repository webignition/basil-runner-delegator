<?php

declare(strict_types=1);

namespace webignition\BasilRunner;

use Symfony\Component\Console\Application;

class ApplicationConfiguration
{
    private $name;
    private $version;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->version = '0.1-beta';
    }

    public function configureApplication(Application $application)
    {
        $application->setName($this->name);
        $application->setVersion($this->version);
    }
}
