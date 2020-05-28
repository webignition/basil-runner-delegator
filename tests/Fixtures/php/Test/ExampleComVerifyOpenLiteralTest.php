<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;

class ExampleComVerifyOpenLiteralTest extends AbstractBaseTest
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
        $this->handledStatements[] = $this->assertionFactory->createFromJson('{
            "statement-type": "assertion",
            "source": "$page.url is \\"https:\\/\\/example.com\\/\\"",
            "identifier": "$page.url",
            "operator": "is",
            "value": "\\"https:\\/\\/example.com\\/\\""
        }');
        $this->setExpectedValue("https://example.com/" ?? null);
        $this->setExaminedValue(self::$client->getCurrentURL() ?? null);
        $this->assertEquals(
            $this->getExpectedValue(),
            $this->getExaminedValue()
        );
    }
}
