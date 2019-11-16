<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;

class Generated1f4ee9e22fcf5ca51b84ef063278e205Test extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com');
    }

    public function testF0f81bc625442f2edd8f05ccc64de6b1()
    {
        // verify page is open
        // $page.url is "https://example.com"
        $expected = "https://example.com" ?? null;
        $expected = (string) $expected;
        $examined = self::$client->getCurrentURL() ?? null;
        $examined = (string) $examined;
        $this->assertEquals($expected, $examined);
    }
}
