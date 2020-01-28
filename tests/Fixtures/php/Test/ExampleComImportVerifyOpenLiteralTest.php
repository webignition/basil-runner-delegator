<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;

class ExampleComImportVerifyOpenLiteralTest extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com/');
        self::setBasilTestPath('{{ test_path }}');
    }

    public function testF0f81bc625442f2edd8f05ccc64de6b1()
    {
        $this->setBasilStepName('verify page is open');

        // $page.url is "https://example.com/"
        $expected = "https://example.com/" ?? null;
        $expected = (string) $expected;
        $examined = self::$client->getCurrentURL() ?? null;
        $examined = (string) $examined;
        $this->assertEquals($expected, $examined);
    }
}
