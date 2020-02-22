<?php

namespace webignition\BasilRunner\Generated;

use webignition\BaseBasilTestCase\AbstractBaseTest;
use webignition\BaseBasilTestCase\Statement;
use webignition\DomElementIdentifier\ElementIdentifier;

class ExampleComFollowMoreInformationTest extends AbstractBaseTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$client->request('GET', 'https://example.com/');
        self::setBasilTestPath('{{ test_path }}');
    }

    public function test0b4535a573cf4196b7e26f0f41e6e2e7()
    {
        $this->setBasilStepName('follow more information');

        // $"a" exists <- click $"a"
        $statement = Statement::createAssertion('$"a" exists');
        $this->currentStatement = $statement;
        $has = $this->navigator->hasOne(ElementIdentifier::fromJson('{"locator":"a"}'));
        $this->assertTrue($has, '{"assertion":{"source":"$\\"a\\" exists","identifier":"$\\"a\\"","comparison":"exists"}}');
        $this->completedStatements[] = $statement;

        // click $"a"
        $statement = Statement::createAction('click $"a"');
        $this->currentStatement = $statement;
        $element = $this->navigator->findOne(ElementIdentifier::fromJson('{"locator":"a"}'));
        $element->click();
        $this->completedStatements[] = $statement;

        // $page.url is "https://www.iana.org/domains/reserved"
        $statement = Statement::createAssertion('$page.url is "https://www.iana.org/domains/reserved"');
        $this->currentStatement = $statement;
        $expected = "https://www.iana.org/domains/reserved" ?? null;
        $examined = self::$client->getCurrentURL() ?? null;
        $this->assertEquals($expected, $examined);
        $this->completedStatements[] = $statement;
    }
}
