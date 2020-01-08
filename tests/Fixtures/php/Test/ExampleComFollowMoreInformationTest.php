<?php

namespace webignition\BasilRunner\Generated;

use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\BaseBasilTestCase\AbstractBaseTest;

class ExampleComFollowMoreInformationTest extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com');
    }

    public function test0b4535a573cf4196b7e26f0f41e6e2e7()
    {
        // follow more information
        // $"a" exists <- click $"a"
        $has = $this->navigator->hasOne(ElementIdentifier::fromJson('{"locator":"a"}'));
        $this->assertTrue($has);

        // click $"a"
        $element = $this->navigator->findOne(ElementIdentifier::fromJson('{"locator":"a"}'));
        $element->click();

        // $page.url is "https://www.iana.org/domains/reserved"
        $expected = "https://www.iana.org/domains/reserved" ?? null;
        $expected = (string) $expected;
        $examined = self::$client->getCurrentURL() ?? null;
        $examined = (string) $examined;
        $this->assertEquals($expected, $examined);
    }
}
