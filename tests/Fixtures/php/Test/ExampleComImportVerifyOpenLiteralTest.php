<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BaseBasilTestCase\Statement;

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
        $statement = Statement::createAssertion('$page.url is "https://example.com/"');
        $this->currentStatement = $statement;
        $expected = "https://example.com/" ?? null;
        $examined = self::$client->getCurrentURL() ?? null;
        $this->assertEquals(
            $expected,
            $examined
        );
        $this->completedStatements[] = $statement;
    }
}
