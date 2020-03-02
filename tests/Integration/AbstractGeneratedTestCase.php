<?php

namespace webignition\BasilRunner\Tests\Integration;

use Facebook\WebDriver\WebDriverDimension;
use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\SymfonyPantherWebServerRunner\WebServerRunner;

abstract class AbstractGeneratedTestCase extends AbstractBaseTest
{
    private const WEB_SERVER_DIR = __DIR__ . '/../Fixtures/html';

    /**
     * @var WebServerRunner
     */
    private static $webServerRunner;

    public static function setUpBeforeClass(): void
    {
        self::$webServerRunner = new WebServerRunner((string) realpath(self::WEB_SERVER_DIR));
        self::$webServerRunner->start();

        parent::setUpBeforeClass();
        self::$client->getWebDriver()->manage()->window()->setSize(new WebDriverDimension(1200, 1100));
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::$webServerRunner->stop();
    }
}
